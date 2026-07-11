<?php

namespace App\Http\Controllers\Api\Mekanik;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeDocument;
use App\Models\OperatingHour;
use App\Models\RepairReport;
use App\Models\RepairRequest;
use App\Services\Rag\KnowledgeIngestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Throwable;

class RepairReportController extends Controller
{
    public function __construct(
        protected KnowledgeIngestionService $knowledgeIngestionService
    ) {
    }

    #[OA\Get(
        path: '/api/repair-reports',
        operationId: 'repairReportIndex',
        tags: ['Repair Report'],
        security: [['bearerSanctum' => []]],
        summary: 'Daftar catatan perbaikan',
        description: 'Mekanik hanya melihat catatan miliknya sendiri.',
        responses: [new OA\Response(response: 200, description: 'Data catatan perbaikan berhasil diambil')]
    )]
    public function index()
    {
        $query = RepairReport::with([
            'repairRequest',
            'asset',
            'mechanic',
            'operatingHour',
        ])->latest();

        if (Auth::user()->role === 'mekanik') {
            $query->where('mechanic_id', Auth::id());
        }

        return response()->json([
            'message' => 'Data catatan perbaikan berhasil diambil',
            'data' => $query->get(),
        ]);
    }

    #[OA\Post(
        path: '/api/repair-reports',
        operationId: 'repairReportStore',
        tags: ['Repair Report'],
        security: [['bearerSanctum' => []]],
        summary: 'Buat catatan perbaikan (khusus mekanik)',
        description: 'Pengajuan harus milik mekanik & berstatus approved, belum ada catatan, jam jalan sesuai aset.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['repair_request_id', 'operating_hour_id', 'tanggal_perbaikan', 'catatan_temuan', 'cara_perbaikan'],
                    properties: [
                        new OA\Property(property: 'repair_request_id', type: 'integer', example: 1),
                        new OA\Property(property: 'operating_hour_id', type: 'integer', example: 5),
                        new OA\Property(property: 'tanggal_perbaikan', type: 'string', format: 'date', example: '2026-06-10'),
                        new OA\Property(property: 'catatan_temuan', type: 'string', example: 'Bearing aus'),
                        new OA\Property(property: 'cara_perbaikan', type: 'string', example: 'Ganti bearing baru'),
                        new OA\Property(property: 'bukti_foto', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: 'llm_suggestion', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Catatan perbaikan berhasil dibuat'),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 422, description: 'Aturan bisnis dilanggar', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'repair_request_id' => ['required', 'exists:repair_requests,id'],
            'operating_hour_id' => ['required', 'exists:operating_hours,id'],
            'tanggal_perbaikan' => ['required', 'date'],
            'catatan_temuan' => ['required', 'string'],
            'cara_perbaikan' => ['required', 'string'],
            'bukti_foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'llm_suggestion' => ['nullable', 'string'],
        ]);

        $repairRequest = RepairRequest::findOrFail($validated['repair_request_id']);

        if ($repairRequest->mechanic_id !== Auth::id()) {
            return response()->json([
                'message' => 'Forbidden. Pengajuan perbaikan ini bukan milik Anda.',
            ], 403);
        }

        if ($repairRequest->status_verifikasi !== 'approved') {
            return response()->json([
                'message' => 'Catatan perbaikan hanya dapat dibuat setelah pengajuan disetujui.',
            ], 422);
        }

        $existingReport = RepairReport::where('repair_request_id', $repairRequest->id)->first();

        if ($existingReport) {
            return response()->json([
                'message' => 'Catatan perbaikan untuk pengajuan ini sudah pernah dibuat.',
            ], 422);
        }

        $operatingHour = OperatingHour::findOrFail($validated['operating_hour_id']);

        if ((int) $operatingHour->asset_id !== (int) $repairRequest->asset_id) {
            return response()->json([
                'message' => 'Data jam jalan tidak sesuai dengan aset pada pengajuan perbaikan.',
            ], 422);
        }

        if ((int) $operatingHour->user_id !== (int) Auth::id()) {
            return response()->json([
                'message' => 'Data jam jalan bukan milik mekanik yang sedang login.',
            ], 403);
        }

        $buktiFotoPath = null;

        if ($request->hasFile('bukti_foto')) {
            $buktiFotoPath = $request->file('bukti_foto')
                ->store('repair-reports/bukti-foto', 'public');
        }

        $report = RepairReport::create([
            'repair_request_id' => $repairRequest->id,
            'asset_id' => $repairRequest->asset_id,
            'mechanic_id' => Auth::id(),
            'operating_hour_id' => $operatingHour->id,
            'tanggal_perbaikan' => $validated['tanggal_perbaikan'],
            'catatan_temuan' => $validated['catatan_temuan'],
            'cara_perbaikan' => $validated['cara_perbaikan'],
            'bukti_foto' => $buktiFotoPath,
            'llm_suggestion' => $validated['llm_suggestion'] ?? null,
        ]);

        $report->load([
            'repairRequest',
            'asset',
            'mechanic',
            'operatingHour',
        ]);

        $this->autoIngestRepairReport($report);

        return response()->json([
            'message' => 'Catatan perbaikan berhasil dibuat',
            'data' => $report,
        ], 201);
    }

    #[OA\Get(
        path: '/api/repair-reports/{id}',
        operationId: 'repairReportShow',
        tags: ['Repair Report'],
        security: [['bearerSanctum' => []]],
        summary: 'Detail catatan perbaikan',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Detail catatan perbaikan berhasil diambil'),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
        ]
    )]
    public function show($id)
    {
        $report = RepairReport::with([
            'repairRequest',
            'asset',
            'mechanic',
            'operatingHour',
        ])->findOrFail($id);

        if (Auth::user()->role === 'mekanik' && $report->mechanic_id !== Auth::id()) {
            return response()->json([
                'message' => 'Forbidden. Catatan ini bukan milik Anda.',
            ], 403);
        }

        return response()->json([
            'message' => 'Detail catatan perbaikan berhasil diambil',
            'data' => $report,
        ]);
    }

    #[OA\Put(
        path: '/api/repair-reports/{id}',
        operationId: 'repairReportUpdate',
        tags: ['Repair Report'],
        security: [['bearerSanctum' => []]],
        summary: 'Perbarui catatan perbaikan',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['operating_hour_id', 'tanggal_perbaikan', 'catatan_temuan', 'cara_perbaikan'],
                    properties: [
                        new OA\Property(property: 'operating_hour_id', type: 'integer', example: 5),
                        new OA\Property(property: 'tanggal_perbaikan', type: 'string', format: 'date', example: '2026-06-10'),
                        new OA\Property(property: 'catatan_temuan', type: 'string'),
                        new OA\Property(property: 'cara_perbaikan', type: 'string'),
                        new OA\Property(property: 'bukti_foto', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: 'llm_suggestion', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Catatan perbaikan berhasil diperbarui'),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 422, description: 'Aturan bisnis dilanggar', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(Request $request, $id)
    {
        $report = RepairReport::findOrFail($id);

        if (Auth::user()->role === 'mekanik' && $report->mechanic_id !== Auth::id()) {
            return response()->json([
                'message' => 'Forbidden. Catatan ini bukan milik Anda.',
            ], 403);
        }

        $validated = $request->validate([
            'operating_hour_id' => ['required', 'exists:operating_hours,id'],
            'tanggal_perbaikan' => ['required', 'date'],
            'catatan_temuan' => ['required', 'string'],
            'cara_perbaikan' => ['required', 'string'],
            'bukti_foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'llm_suggestion' => ['nullable', 'string'],
        ]);

        $operatingHour = OperatingHour::findOrFail($validated['operating_hour_id']);

        if ((int) $operatingHour->asset_id !== (int) $report->asset_id) {
            return response()->json([
                'message' => 'Data jam jalan tidak sesuai dengan aset catatan perbaikan.',
            ], 422);
        }

        if (
            Auth::user()->role === 'mekanik' &&
            (int) $operatingHour->user_id !== (int) Auth::id()
        ) {
            return response()->json([
                'message' => 'Data jam jalan bukan milik mekanik yang sedang login.',
            ], 403);
        }

        if ($request->hasFile('bukti_foto')) {
            if ($report->bukti_foto) {
                Storage::disk('public')->delete($report->bukti_foto);
            }

            $validated['bukti_foto'] = $request->file('bukti_foto')
                ->store('repair-reports/bukti-foto', 'public');
        }

        $report->update($validated);
        $report->refresh();

        $report->load([
            'repairRequest',
            'asset',
            'mechanic',
            'operatingHour',
        ]);

        $this->autoIngestRepairReport($report);

        return response()->json([
            'message' => 'Catatan perbaikan berhasil diperbarui',
            'data' => $report,
        ]);
    }

    #[OA\Delete(
        path: '/api/repair-reports/{id}',
        operationId: 'repairReportDestroy',
        tags: ['Repair Report'],
        security: [['bearerSanctum' => []]],
        summary: 'Hapus catatan perbaikan',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Catatan perbaikan berhasil dihapus', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
        ]
    )]
    public function destroy($id)
    {
        $report = RepairReport::findOrFail($id);

        if (Auth::user()->role === 'mekanik' && $report->mechanic_id !== Auth::id()) {
            return response()->json([
                'message' => 'Forbidden. Catatan ini bukan milik Anda.',
            ], 403);
        }

        if ($report->bukti_foto) {
            Storage::disk('public')->delete($report->bukti_foto);
        }

        KnowledgeDocument::where('source_type', 'repair_report')
            ->where('source_id', $report->id)
            ->delete();

        $report->delete();

        return response()->json([
            'message' => 'Catatan perbaikan berhasil dihapus',
        ]);
    }

    private function autoIngestRepairReport(RepairReport $report): void
    {
        try {
            $this->knowledgeIngestionService->ingestRepairReport($report);
        } catch (Throwable $e) {
            report($e);
        }
    }
}