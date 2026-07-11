<?php

namespace App\Http\Controllers\Api\Mekanik;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeDocument;
use App\Models\MaintenanceReport;
use App\Models\MaintenanceTask;
use App\Models\OperatingHour;
use App\Services\Rag\KnowledgeIngestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Throwable;

class MaintenanceReportController extends Controller
{
    public function __construct(
        protected KnowledgeIngestionService $knowledgeIngestionService
    ) {
    }

    #[OA\Get(
        path: '/api/maintenance-reports',
        operationId: 'maintenanceReportIndex',
        tags: ['Maintenance Report'],
        security: [['bearerSanctum' => []]],
        summary: 'Daftar laporan pemeliharaan',
        description: 'Mekanik hanya melihat laporan miliknya sendiri.',
        responses: [new OA\Response(response: 200, description: 'Data laporan pemeliharaan berhasil diambil')]
    )]
    public function index()
    {
        $query = MaintenanceReport::with([
            'maintenanceTask',
            'asset',
            'mechanic',
            'operatingHour',
        ])->latest();

        if (Auth::user()->role === 'mekanik') {
            $query->where('mechanic_id', Auth::id());
        }

        return response()->json([
            'message' => 'Data laporan pemeliharaan berhasil diambil',
            'data' => $query->get(),
        ]);
    }

    #[OA\Post(
        path: '/api/maintenance-reports',
        operationId: 'maintenanceReportStore',
        tags: ['Maintenance Report'],
        security: [['bearerSanctum' => []]],
        summary: 'Buat laporan pemeliharaan (khusus mekanik)',
        description: 'Tugas harus milik mekanik yang login, belum completed, jam jalan sesuai aset, dan belum ada laporan untuk tugas tsb.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['maintenance_task_id', 'operating_hour_id', 'tanggal_pemeliharaan', 'catatan_pemeliharaan'],
                    properties: [
                        new OA\Property(property: 'maintenance_task_id', type: 'integer', example: 1),
                        new OA\Property(property: 'operating_hour_id', type: 'integer', example: 5),
                        new OA\Property(property: 'tanggal_pemeliharaan', type: 'string', format: 'date', example: '2026-06-10'),
                        new OA\Property(property: 'catatan_pemeliharaan', type: 'string', example: 'Ganti oli & filter'),
                        new OA\Property(property: 'bukti_foto', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: 'llm_suggestion', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Laporan pemeliharaan berhasil dibuat'),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 422, description: 'Aturan bisnis dilanggar', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'maintenance_task_id' => ['required', 'exists:maintenance_tasks,id'],
            'operating_hour_id' => ['required', 'exists:operating_hours,id'],
            'tanggal_pemeliharaan' => ['required', 'date'],
            'catatan_pemeliharaan' => ['required', 'string'],
            'bukti_foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'llm_suggestion' => ['nullable', 'string'],
        ]);

        $task = MaintenanceTask::findOrFail($validated['maintenance_task_id']);

        if ($task->assigned_to !== Auth::id()) {
            return response()->json(['message' => 'Forbidden. Tugas ini bukan milik Anda.'], 403);
        }

        if ($task->status === 'completed') {
            return response()->json(['message' => 'Tugas ini sudah selesai dan tidak dapat dilaporkan ulang.'], 422);
        }

        $operatingHour = OperatingHour::findOrFail($validated['operating_hour_id']);

        if ((int) $operatingHour->asset_id !== (int) $task->asset_id) {
            return response()->json(['message' => 'Data jam jalan tidak sesuai dengan aset pada tugas pemeliharaan.'], 422);
        }

        if ((int) $operatingHour->user_id !== (int) Auth::id()) {
            return response()->json(['message' => 'Data jam jalan bukan milik mekanik yang sedang login.'], 403);
        }

        $existingReport = MaintenanceReport::where('maintenance_task_id', $task->id)->first();

        if ($existingReport) {
            return response()->json(['message' => 'Laporan untuk tugas ini sudah pernah dibuat.'], 422);
        }

        $buktiFotoPath = null;

        if ($request->hasFile('bukti_foto')) {
            $buktiFotoPath = $request->file('bukti_foto')
                ->store('maintenance-reports/bukti-foto', 'public');
        }

        $report = MaintenanceReport::create([
            'maintenance_task_id' => $task->id,
            'asset_id' => $task->asset_id,
            'mechanic_id' => Auth::id(),
            'operating_hour_id' => $operatingHour->id,
            'tanggal_pemeliharaan' => $validated['tanggal_pemeliharaan'],
            'catatan_pemeliharaan' => $validated['catatan_pemeliharaan'],
            'bukti_foto' => $buktiFotoPath,
            'llm_suggestion' => $validated['llm_suggestion'] ?? null,
        ]);

        $task->update([
            'status' => 'submitted',
        ]);

        $report->load([
            'maintenanceTask',
            'asset',
            'mechanic',
            'operatingHour',
        ]);

        $this->autoIngestMaintenanceReport($report);

        return response()->json([
            'message' => 'Laporan pemeliharaan berhasil dibuat',
            'data' => $report,
        ], 201);
    }

    #[OA\Get(
        path: '/api/maintenance-reports/{id}',
        operationId: 'maintenanceReportShow',
        tags: ['Maintenance Report'],
        security: [['bearerSanctum' => []]],
        summary: 'Detail laporan pemeliharaan',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Detail laporan pemeliharaan berhasil diambil'),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
        ]
    )]
    public function show($id)
    {
        $report = MaintenanceReport::with([
            'maintenanceTask',
            'asset',
            'mechanic',
            'operatingHour',
        ])->findOrFail($id);

        if (Auth::user()->role === 'mekanik' && $report->mechanic_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden. Laporan ini bukan milik Anda.'], 403);
        }

        return response()->json([
            'message' => 'Detail laporan pemeliharaan berhasil diambil',
            'data' => $report,
        ]);
    }

    #[OA\Put(
        path: '/api/maintenance-reports/{id}',
        operationId: 'maintenanceReportUpdate',
        tags: ['Maintenance Report'],
        security: [['bearerSanctum' => []]],
        summary: 'Perbarui laporan pemeliharaan',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['operating_hour_id', 'tanggal_pemeliharaan', 'catatan_pemeliharaan'],
                    properties: [
                        new OA\Property(property: 'operating_hour_id', type: 'integer', example: 5),
                        new OA\Property(property: 'tanggal_pemeliharaan', type: 'string', format: 'date', example: '2026-06-10'),
                        new OA\Property(property: 'catatan_pemeliharaan', type: 'string'),
                        new OA\Property(property: 'bukti_foto', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: 'llm_suggestion', type: 'string', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Laporan pemeliharaan berhasil diperbarui'),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 422, description: 'Aturan bisnis dilanggar', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(Request $request, $id)
    {
        $report = MaintenanceReport::findOrFail($id);

        if (Auth::user()->role === 'mekanik' && $report->mechanic_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden. Laporan ini bukan milik Anda.'], 403);
        }

        $validated = $request->validate([
            'operating_hour_id' => ['required', 'exists:operating_hours,id'],
            'tanggal_pemeliharaan' => ['required', 'date'],
            'catatan_pemeliharaan' => ['required', 'string'],
            'bukti_foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'llm_suggestion' => ['nullable', 'string'],
        ]);

        $operatingHour = OperatingHour::findOrFail($validated['operating_hour_id']);

        if ((int) $operatingHour->asset_id !== (int) $report->asset_id) {
            return response()->json(['message' => 'Data jam jalan tidak sesuai dengan aset laporan pemeliharaan.'], 422);
        }

        if (Auth::user()->role === 'mekanik' && (int) $operatingHour->user_id !== (int) Auth::id()) {
            return response()->json(['message' => 'Data jam jalan bukan milik mekanik yang sedang login.'], 403);
        }

        if ($request->hasFile('bukti_foto')) {
            if ($report->bukti_foto) {
                Storage::disk('public')->delete($report->bukti_foto);
            }

            $validated['bukti_foto'] = $request->file('bukti_foto')
                ->store('maintenance-reports/bukti-foto', 'public');
        }

        $report->update($validated);
        $report->refresh();

        $report->load([
            'maintenanceTask',
            'asset',
            'mechanic',
            'operatingHour',
        ]);

        $this->autoIngestMaintenanceReport($report);

        return response()->json([
            'message' => 'Laporan pemeliharaan berhasil diperbarui',
            'data' => $report,
        ]);
    }

    #[OA\Delete(
        path: '/api/maintenance-reports/{id}',
        operationId: 'maintenanceReportDestroy',
        tags: ['Maintenance Report'],
        security: [['bearerSanctum' => []]],
        summary: 'Hapus laporan pemeliharaan',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Laporan pemeliharaan berhasil dihapus', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
        ]
    )]
    public function destroy($id)
    {
        $report = MaintenanceReport::findOrFail($id);

        if (Auth::user()->role === 'mekanik' && $report->mechanic_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden. Laporan ini bukan milik Anda.'], 403);
        }

        if ($report->bukti_foto) {
            Storage::disk('public')->delete($report->bukti_foto);
        }

        KnowledgeDocument::where('source_type', 'maintenance_report')
            ->where('source_id', $report->id)
            ->delete();

        $report->delete();

        return response()->json([
            'message' => 'Laporan pemeliharaan berhasil dihapus',
        ]);
    }

    private function autoIngestMaintenanceReport(MaintenanceReport $report): void
    {
        try {
            $this->knowledgeIngestionService->ingestMaintenanceReport($report);
        } catch (Throwable $e) {
            report($e);
        }
    }
}