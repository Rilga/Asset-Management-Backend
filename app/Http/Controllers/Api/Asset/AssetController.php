<?php

namespace App\Http\Controllers\Api\Asset;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Services\Rag\KnowledgeIngestionService;
use Throwable;
use Illuminate\Support\Facades\Log;
use App\Services\Storage\SupabaseStorageService;
use OpenApi\Attributes as OA;

class AssetController extends Controller
{
    public function __construct(
        protected KnowledgeIngestionService $knowledgeIngestionService,
        protected SupabaseStorageService $supabaseStorageService
    ) {
    }

    #[OA\Get(
        path: '/api/assets',
        operationId: 'assetsIndex',
        tags: ['Assets'],
        security: [['bearerSanctum' => []]],
        summary: 'List aset',
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index()
    {
        $assets = Asset::with('creator')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Data aset berhasil diambil',
            'data' => $assets
        ]);
    }

    #[OA\Post(
        path: '/api/assets',
        operationId: 'assetsStore',
        tags: ['Assets'],
        security: [['bearerSanctum' => []]],
        summary: 'Buat aset baru (teknik)',
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['kategori', 'nomor_peralatan', 'nama_mesin', 'area_mesin', 'maintenance_interval_hours'],
                properties: [
                    new OA\Property(property: 'kategori', type: 'string'),
                    new OA\Property(property: 'nomor_peralatan', type: 'string'),
                    new OA\Property(property: 'nama_mesin', type: 'string'),
                    new OA\Property(property: 'area_mesin', type: 'string'),
                    new OA\Property(property: 'merek', type: 'string', nullable: true),
                    new OA\Property(property: 'tahun_pembelian', type: 'integer', nullable: true),
                    new OA\Property(property: 'maintenance_interval_hours', type: 'integer'),
                    new OA\Property(property: 'foto_kondisi', type: 'string', format: 'binary', nullable: true),
                    new OA\Property(property: 'manual_book', type: 'string', format: 'binary', nullable: true),
                ]
            )
        )),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kategori' => ['required', 'string', 'max:255'],
            'nomor_peralatan' => ['required', 'string', 'max:255', 'unique:assets,nomor_peralatan'],
            'nama_mesin' => ['required', 'string', 'max:255'],
            'area_mesin' => ['required', 'string', 'max:255'],
            'merek' => ['nullable', 'string', 'max:255'],
            'tahun_pembelian' => ['nullable', 'digits:4', 'integer', 'min:1900', 'max:' . date('Y')],
            'maintenance_interval_hours' => ['required', 'integer', 'min:1'],
            'foto_kondisi' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'manual_book' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        if ($request->hasFile('foto_kondisi')) {
            $validated['foto_kondisi'] = $request->file('foto_kondisi')
                ->store('assets/foto-kondisi', 'public');
        }

        if ($request->hasFile('manual_book')) {
            $validated['manual_book'] = $this->supabaseStorageService
                ->uploadPdf($request->file('manual_book'));
        }

        $validated['created_by'] = Auth::id();

        $asset = Asset::create($validated);
        $this->generateQrCode($asset);
        $asset->refresh();
        $this->autoIngestAsset($asset);
        $asset->refresh();

        return response()->json([
            'message' => 'Data aset berhasil dibuat',
            'data' => $asset
        ], 201);
    }

    #[OA\Get(
        path: '/api/assets/{id}',
        operationId: 'assetsShow',
        tags: ['Assets'],
        security: [['bearerSanctum' => []]],
        summary: 'Detail aset',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail aset berhasil diambil'),
            new OA\Response(response: 404, description: 'Aset tidak ditemukan'),
        ]
    )]
    public function show($id)
    {
        $asset = Asset::with('creator')->findOrFail($id);

        return response()->json([
            'message' => 'Detail aset berhasil diambil',
            'data' => $asset
        ]);
    }

    #[OA\Put(
        path: '/api/assets/{id}',
        operationId: 'assetsUpdate',
        tags: ['Assets'],
        security: [['bearerSanctum' => []]],
        summary: 'Perbarui aset (khusus teknik)',
        description: 'Memperbarui aset. Menggunakan multipart/form-data karena bisa mengunggah foto kondisi & manual book (PDF).',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['kategori', 'nomor_peralatan', 'nama_mesin', 'area_mesin', 'maintenance_interval_hours'],
                    properties: [
                        new OA\Property(property: 'kategori', type: 'string', example: 'Mesin Produksi'),
                        new OA\Property(property: 'nomor_peralatan', type: 'string', example: 'P-10'),
                        new OA\Property(property: 'nama_mesin', type: 'string', example: 'Mesin Screw Press P-10'),
                        new OA\Property(property: 'area_mesin', type: 'string', example: 'Stasiun Press'),
                        new OA\Property(property: 'merek', type: 'string', nullable: true, example: 'Stork'),
                        new OA\Property(property: 'tahun_pembelian', type: 'integer', nullable: true, example: 2020),
                        new OA\Property(property: 'maintenance_interval_hours', type: 'integer', example: 2000),
                        new OA\Property(property: 'foto_kondisi', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: 'manual_book', type: 'string', format: 'binary', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Data aset berhasil diperbarui'),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'kategori' => ['required', 'string', 'max:255'],
            'nomor_peralatan' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assets', 'nomor_peralatan')->ignore($asset->id),
            ],
            'nama_mesin' => ['required', 'string', 'max:255'],
            'area_mesin' => ['required', 'string', 'max:255'],
            'merek' => ['nullable', 'string', 'max:255'],
            'tahun_pembelian' => ['nullable', 'digits:4', 'integer', 'min:1900', 'max:' . date('Y')],
            'maintenance_interval_hours' => ['required', 'integer', 'min:1'],
            'foto_kondisi' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'manual_book' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        if ($request->hasFile('foto_kondisi')) {
            if ($asset->foto_kondisi) {
                Storage::disk('public')->delete($asset->foto_kondisi);
            }

            $validated['foto_kondisi'] = $request->file('foto_kondisi')
                ->store('assets/foto-kondisi', 'public');
        }

        if ($request->hasFile('manual_book')) {
            if ($asset->manual_book) {
                $this->supabaseStorageService->delete($asset->manual_book);
            }

            $validated['manual_book'] = $this->supabaseStorageService
                ->uploadPdf($request->file('manual_book'));
        }

        $asset->update($validated);

        $this->generateQrCode($asset);

        $asset->refresh();
        $this->autoIngestAsset($asset);
        $asset->refresh();

        return response()->json([
            'message' => 'Data aset berhasil diperbarui',
            'data' => $asset
        ]);
    }

    #[OA\Delete(
        path: '/api/assets/{id}',
        operationId: 'assetsDestroy',
        tags: ['Assets'],
        security: [['bearerSanctum' => []]],
        summary: 'Hapus aset (khusus teknik)',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data aset berhasil dihapus', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 404, description: 'Aset tidak ditemukan'),
        ]
    )]
    public function destroy($id)
    {
        $asset = Asset::findOrFail($id);

        if ($asset->foto_kondisi) {
            Storage::disk('public')->delete($asset->foto_kondisi);
        }

        if ($asset->manual_book) {
            $this->supabaseStorageService->delete($asset->manual_book);
        }

        if ($asset->qr_code_path) {
            Storage::disk('public')->delete($asset->qr_code_path);
        }

        // Hapus knowledge document milik asset.
        // Knowledge chunks ikut terhapus karena cascadeOnDelete pada knowledge_document_id.
        $asset->knowledgeDocuments()->delete();

        $asset->delete();

        return response()->json([
            'message' => 'Data aset berhasil dihapus'
        ]);
    }

    #[OA\Get(
        path: '/api/assets/{asset}/qr-detail',
        operationId: 'assetsQrDetail',
        tags: ['Assets'],
        summary: 'Detail aset dari QR code (publik)',
        description: 'Endpoint publik (tanpa autentikasi) yang ditautkan pada QR code aset. Mengembalikan profil aset + ringkasan jam operasi, histori pemeliharaan, dan histori perbaikan.',
        parameters: [
            new OA\Parameter(name: 'asset', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail aset dari QR code berhasil diambil'),
            new OA\Response(response: 404, description: 'Aset tidak ditemukan'),
        ]
    )]
    public function qrDetail(Asset $asset)
    {
        $asset->load([
            'creator',
            'operatingHours.user',
            'maintenanceReports.maintenanceTask',
            'maintenanceReports.mechanic',
            'maintenanceReports.operatingHour',
            'repairReports.repairRequest',
            'repairReports.mechanic',
            'repairReports.operatingHour',
        ]);

        $latestOperatingHour = $asset->operatingHours
            ->sortByDesc('tanggal')
            ->first();

        return response()->json([
            'message' => 'Detail aset dari QR code berhasil diambil',
            'data' => [
                'asset' => $asset,
                'summary' => [
                    'latest_operating_hour' => $latestOperatingHour,
                    'total_operating_hours' => $asset->operatingHours->sum('jam_jalan'),
                    'maintenance_history_count' => $asset->maintenanceReports->count(),
                    'repair_history_count' => $asset->repairReports->count(),
                ],
                'operating_hours' => $asset->operatingHours
                    ->sortByDesc('tanggal')
                    ->values(),
                'maintenance_history' => $asset->maintenanceReports
                    ->sortByDesc('tanggal_pemeliharaan')
                    ->values(),
                'repair_history' => $asset->repairReports
                    ->sortByDesc('tanggal_perbaikan')
                    ->values(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/assets/{asset}/qr-code',
        operationId: 'assetsRegenerateQrCode',
        tags: ['Assets'],
        security: [['bearerSanctum' => []]],
        summary: 'Buat ulang QR code aset (khusus teknik)',
        parameters: [
            new OA\Parameter(name: 'asset', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'QR code aset berhasil dibuat ulang'),
            new OA\Response(response: 404, description: 'Aset tidak ditemukan'),
        ]
    )]
    public function regenerateQrCode(Asset $asset)
    {
        $this->generateQrCode($asset);
        $asset->refresh();

        return response()->json([
            'message' => 'QR code aset berhasil dibuat ulang',
            'data' => $asset,
        ]);
    }

    private function generateQrCode(Asset $asset): void
    {
        $path = 'assets/qrcodes/asset-' . $asset->id . '.svg';

        Storage::disk('public')->put(
            $path,
            QrCode::format('svg')
                ->size(300)
                ->margin(2)
                ->generate(route('assets.qr-detail', $asset))
        );

        $asset->update([
            'qr_code_path' => $path,
        ]);
    }

    private function autoIngestAsset(Asset $asset): void
    {
        try {
            $this->knowledgeIngestionService->ingestAssetProfile($asset);

            if ($asset->manual_book) {
                $this->knowledgeIngestionService->ingestAssetManualBook($asset);
            }
        } catch (Throwable $e) {
            Log::error('Auto ingestion asset gagal', [
                'asset_id' => $asset->id,
                'message' => $e->getMessage(),
            ]);

            report($e);
        }
    }

    #[OA\Get(
        path: '/api/assets/{id}/knowledge-summary',
        operationId: 'assetsKnowledgeSummary',
        tags: ['Assets'],
        security: [['bearerSanctum' => []]],
        summary: 'Ringkasan knowledge aset',
        description: 'Mengembalikan jumlah knowledge document, knowledge chunks, laporan pemeliharaan, catatan perbaikan, dan jam operasi terbaru untuk satu aset.',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Ringkasan knowledge aset berhasil diambil'),
            new OA\Response(response: 404, description: 'Aset tidak ditemukan'),
        ]
    )]
    public function knowledgeSummary($id)
    {
        $asset = Asset::with([
            'knowledgeDocuments.chunks',
            'maintenanceReports',
            'repairReports',
            'operatingHours',
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Ringkasan knowledge aset berhasil diambil',
            'data' => [
                'asset' => $asset,
                'summary' => [
                    'knowledge_documents_count' => $asset->knowledgeDocuments->count(),
                    'knowledge_chunks_count' => $asset->knowledgeDocuments
                        ->sum(fn ($document) => $document->chunks->count()),
                    'maintenance_reports_count' => $asset->maintenanceReports->count(),
                    'repair_reports_count' => $asset->repairReports->count(),
                    'operating_hours_count' => $asset->operatingHours->count(),
                    'latest_operating_hour' => $asset->operatingHours
                        ->sortByDesc('tanggal')
                        ->first(),
                ],
                'knowledge_documents' => $asset->knowledgeDocuments,
            ],
        ]);
    }
}
