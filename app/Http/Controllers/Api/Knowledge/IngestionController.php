<?php

namespace App\Http\Controllers\Api\Knowledge;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\MaintenanceReport;
use App\Models\RepairReport;
use App\Services\Rag\KnowledgeIngestionService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Throwable;

class IngestionController extends Controller
{
    public function __construct(
        protected KnowledgeIngestionService $knowledgeIngestionService
    ) {
    }

    #[OA\Post(
        path: '/api/ingestion/assets/{asset}/profile',
        operationId: 'ingestAssetProfile',
        tags: ['Knowledge'],
        security: [['bearerSanctum' => []]],
        summary: 'Ingest profil aset ke knowledge base (khusus teknik)',
        description: 'Membuat embedding profil aset via AI Engine lalu menyimpannya ke pgvector.',
        parameters: [new OA\Parameter(name: 'asset', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 201, description: 'Profil aset berhasil di-ingest'),
            new OA\Response(response: 500, description: 'Gagal ingest profil aset'),
        ]
    )]
    public function ingestAssetProfile($assetId)
    {
        try {
            $asset = Asset::findOrFail($assetId);

            $document = $this->knowledgeIngestionService
                ->ingestAssetProfile($asset);

            return response()->json([
                'message' => 'Profil aset berhasil di-ingest',
                'data' => $document,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Gagal ingest profil aset',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/ingestion/assets/{asset}/manual-book',
        operationId: 'ingestAssetManualBook',
        tags: ['Knowledge'],
        security: [['bearerSanctum' => []]],
        summary: 'Ingest manual book aset (PDF) ke knowledge base (khusus teknik)',
        description: 'Mengekstrak teks PDF manual book, membuat embedding via AI Engine, lalu menyimpannya ke pgvector.',
        parameters: [new OA\Parameter(name: 'asset', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 201, description: 'Manual book aset berhasil di-ingest'),
            new OA\Response(response: 500, description: 'Gagal ingest manual book aset'),
        ]
    )]
    public function ingestAssetManualBook($assetId)
    {
        try {
            $asset = Asset::findOrFail($assetId);

            $document = $this->knowledgeIngestionService
                ->ingestAssetManualBook($asset);

            return response()->json([
                'message' => 'Manual book aset berhasil di-ingest',
                'data' => $document,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Gagal ingest manual book aset',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/ingestion/maintenance-reports/{id}',
        operationId: 'ingestMaintenanceReport',
        tags: ['Knowledge'],
        security: [['bearerSanctum' => []]],
        summary: 'Ingest laporan pemeliharaan ke knowledge base (khusus teknik)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 201, description: 'Laporan pemeliharaan berhasil di-ingest'),
            new OA\Response(response: 500, description: 'Gagal ingest laporan pemeliharaan'),
        ]
    )]
    public function ingestMaintenanceReport($id)
    {
        try {
            $report = MaintenanceReport::findOrFail($id);

            $document = $this->knowledgeIngestionService
                ->ingestMaintenanceReport($report);

            return response()->json([
                'message' => 'Laporan pemeliharaan berhasil di-ingest',
                'data' => $document,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Gagal ingest laporan pemeliharaan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/ingestion/upload',
        operationId: 'ingestUploadedDocument',
        tags: ['Knowledge'],
        security: [['bearerSanctum' => []]],
        summary: 'Upload dan ingest dokumen bebas ke knowledge base (khusus teknik)',
        description: 'Mengunggah file (PDF, DOCX, atau TXT), mengekstrak teksnya, membuat embedding via AI Engine, lalu menyimpannya ke pgvector.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                        new OA\Property(property: 'asset_id', type: 'integer', nullable: true),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Dokumen berhasil di-upload dan di-ingest'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
            new OA\Response(response: 500, description: 'Gagal upload/ingest dokumen'),
        ]
    )]
    public function uploadDocument(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,docx,txt', 'max:10240'],
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $asset = isset($validated['asset_id'])
                ? Asset::findOrFail($validated['asset_id'])
                : null;

            $document = $this->knowledgeIngestionService->ingestUploadedDocument(
                $request->file('file'),
                $asset,
                $validated['description'] ?? null
            );

            return response()->json([
                'message' => 'Dokumen berhasil di-upload dan di-ingest',
                'data' => $document,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Gagal upload/ingest dokumen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/ingestion/repair-reports/{id}',
        operationId: 'ingestRepairReport',
        tags: ['Knowledge'],
        security: [['bearerSanctum' => []]],
        summary: 'Ingest catatan perbaikan ke knowledge base (khusus teknik)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 201, description: 'Catatan perbaikan berhasil di-ingest'),
            new OA\Response(response: 500, description: 'Gagal ingest catatan perbaikan'),
        ]
    )]
    public function ingestRepairReport($id)
    {
        try {
            $report = RepairReport::findOrFail($id);

            $document = $this->knowledgeIngestionService
                ->ingestRepairReport($report);

            return response()->json([
                'message' => 'Catatan perbaikan berhasil di-ingest',
                'data' => $document,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Gagal ingest catatan perbaikan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}