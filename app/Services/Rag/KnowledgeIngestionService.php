<?php

namespace App\Services\Rag;

use App\Models\Asset;
use App\Models\KnowledgeDocument;
use App\Models\MaintenanceReport;
use App\Models\RepairReport;
use App\Services\Storage\SupabaseStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class KnowledgeIngestionService
{
    public function __construct(
        protected ChunkingService $chunkingService,
        protected EmbeddingService $embeddingService,
        protected PdfExtractionService $pdfExtractionService,
        protected UploadedDocumentExtractionService $uploadedDocumentExtractionService,
        protected SupabaseStorageService $supabaseStorageService,
    ) {
    }

    public function ingestUploadedDocument(UploadedFile $file, ?Asset $asset, ?string $description): KnowledgeDocument
    {
        $content = $this->uploadedDocumentExtractionService->extract($file);

        $folder = $asset ? 'knowledge-uploads/assets/' . $asset->id : 'knowledge-uploads/general';

        // The Supabase bucket only allows PDF mime types, so non-PDF uploads
        // (docx, txt) are kept on local public storage instead.
        $storedPath = strtolower($file->getClientOriginalExtension()) === 'pdf'
            ? $this->supabaseStorageService->uploadPdf($file, $folder)
            : $file->store($folder, 'public');

        $title = $description ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        return $this->ingestTextSource(
            asset: $asset,
            sourceType: 'manual_upload',
            sourceId: null,
            title: $title,
            content: $content,
            filePath: $storedPath
        );
    }

    public function ingestAssetProfile(Asset $asset): KnowledgeDocument
    {
        $content = $this->buildAssetProfileContent($asset);

        return $this->ingestTextSource(
            asset: $asset,
            sourceType: 'asset',
            sourceId: $asset->id,
            title: 'Profil Aset - ' . $asset->nama_mesin,
            content: $content,
            filePath: null
        );
    }

    public function ingestAssetManualBook(Asset $asset): KnowledgeDocument
    {
        if (!$asset->manual_book) {
            throw new \RuntimeException('Aset ini belum memiliki manual book.');
        }

        $content = $this->pdfExtractionService->extractText($asset->manual_book);
        
        return $this->ingestTextSource(
            asset: $asset,
            sourceType: 'manual_book',
            sourceId: $asset->id,
            title: 'Manual Book - ' . $asset->nama_mesin,
            content: $content,
            filePath: $asset->manual_book
        );
    }

    public function ingestMaintenanceReport(MaintenanceReport $report): KnowledgeDocument
    {
        $report->load(['asset', 'mechanic', 'operatingHour']);

        $asset = $report->asset;

        $content = implode("\n", [
            'Jenis Dokumen: Laporan Pemeliharaan',
            'Aset: ' . $asset->nama_mesin,
            'Nomor Peralatan: ' . $asset->nomor_peralatan,
            'Area Mesin: ' . $asset->area_mesin,
            'Tanggal Pemeliharaan: ' . $report->tanggal_pemeliharaan,
            'Jam Jalan: ' . optional($report->operatingHour)->jam_jalan,
            'Mekanik: ' . optional($report->mechanic)->name,
            'Catatan Pemeliharaan: ' . $report->catatan_pemeliharaan,
            'Saran LLM: ' . ($report->llm_suggestion ?? '-'),
        ]);

        return $this->ingestTextSource(
            asset: $asset,
            sourceType: 'maintenance_report',
            sourceId: $report->id,
            title: 'Laporan Pemeliharaan - ' . $asset->nama_mesin,
            content: $content,
            filePath: $report->bukti_foto
        );
    }

    public function ingestRepairReport(RepairReport $report): KnowledgeDocument
    {
        $report->load(['asset', 'mechanic', 'operatingHour']);

        $asset = $report->asset;

        $content = implode("\n", [
            'Jenis Dokumen: Catatan Perbaikan',
            'Aset: ' . $asset->nama_mesin,
            'Nomor Peralatan: ' . $asset->nomor_peralatan,
            'Area Mesin: ' . $asset->area_mesin,
            'Tanggal Perbaikan: ' . $report->tanggal_perbaikan,
            'Jam Jalan: ' . optional($report->operatingHour)->jam_jalan,
            'Mekanik: ' . optional($report->mechanic)->name,
            'Catatan Temuan: ' . $report->catatan_temuan,
            'Cara Perbaikan: ' . $report->cara_perbaikan,
            'Saran LLM: ' . ($report->llm_suggestion ?? '-'),
        ]);

        return $this->ingestTextSource(
            asset: $asset,
            sourceType: 'repair_report',
            sourceId: $report->id,
            title: 'Catatan Perbaikan - ' . $asset->nama_mesin,
            content: $content,
            filePath: $report->bukti_foto
        );
    }

    private function ingestTextSource(
        ?Asset $asset,
        string $sourceType,
        ?int $sourceId,
        string $title,
        string $content,
        ?string $filePath = null
    ): KnowledgeDocument {
        return DB::transaction(function () use ($asset, $sourceType, $sourceId, $title, $content, $filePath) {

            if ($sourceId !== null) {
                KnowledgeDocument::where('asset_id', $asset?->id)
                    ->where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->delete();
            }

            $document = KnowledgeDocument::create([
                'asset_id' => $asset?->id,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'title' => $title,
                'content' => $content,
                'file_path' => $filePath,
                'status' => 'pending',
            ]);

            try {
                $chunks = $this->chunkingService->chunk($content);

                if (empty($chunks)) {
                    throw new \RuntimeException('Konten kosong atau tidak dapat di-chunk.');
                }

                $embeddings = $this->embeddingService->embedTexts($chunks);

                foreach ($chunks as $index => $chunkText) {
                    $embedding = $embeddings[$index] ?? null;

                    if (!$embedding) {
                        throw new \RuntimeException('Embedding tidak ditemukan untuk chunk ke-' . $index);
                    }

                    $metadata = [
                        'source_type' => $sourceType,
                        'source_id' => $sourceId,
                        'asset_id' => $asset?->id,
                        'asset_name' => $asset?->nama_mesin,
                        'nomor_peralatan' => $asset?->nomor_peralatan,
                        'area_mesin' => $asset?->area_mesin,
                        'chunk_index' => $index,
                    ];

                    DB::statement(
                        "INSERT INTO knowledge_chunks
                        (knowledge_document_id, asset_id, chunk_text, metadata, embedding, created_at, updated_at)
                        VALUES (?, ?, ?, ?::jsonb, ?::vector, NOW(), NOW())",
                        [
                            $document->id,
                            $asset?->id,
                            $chunkText,
                            json_encode($metadata),
                            $this->vectorToSql($embedding),
                        ]
                    );
                }

                $document->update([
                    'status' => 'processed',
                ]);

                return $document->load(['asset', 'chunks']);
            } catch (Throwable $e) {
                $document->update([
                    'status' => 'failed',
                ]);

                throw $e;
            }
        });
    }

    private function buildAssetProfileContent(Asset $asset): string
    {
        return implode("\n", [
            'Jenis Dokumen: Profil Aset',
            'Nama Mesin: ' . $asset->nama_mesin,
            'Nomor Peralatan: ' . $asset->nomor_peralatan,
            'Kategori: ' . $asset->kategori,
            'Area Mesin: ' . $asset->area_mesin,
            'Merek: ' . ($asset->merek ?? '-'),
            'Tahun Pembelian: ' . ($asset->tahun_pembelian ?? '-'),
            'Interval Maintenance: ' . $asset->maintenance_interval_hours . ' jam',
        ]);
    }

    private function vectorToSql(array $vector): string
    {
        return '[' . implode(',', array_map('floatval', $vector)) . ']';
    }
}