<?php

namespace App\Http\Controllers\Api\Teknik;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Asset;
use App\Models\OperatingHour;
use App\Models\MaintenanceReport;
use OpenApi\Attributes as OA;

class MaintenanceTaskController extends Controller
{
    #[OA\Get(
        path: '/api/maintenance-tasks',
        operationId: 'maintenanceTaskIndex',
        tags: ['Maintenance Task'],
        security: [['bearerSanctum' => []]],
        summary: 'Daftar tugas pemeliharaan',
        description: 'Mekanik hanya melihat tugas yang di-assign kepadanya.',
        responses: [new OA\Response(response: 200, description: 'Data tugas pemeliharaan berhasil diambil')]
    )]
    public function index()
    {
        $query = MaintenanceTask::with(['asset', 'assignedBy', 'assignedTo'])
            ->latest();

        if (Auth::user()->role === 'mekanik') {
            $query->where('assigned_to', Auth::id());
        }

        return response()->json([
            'message' => 'Data tugas pemeliharaan berhasil diambil',
            'data' => $query->get()
        ]);
    }

    #[OA\Post(
        path: '/api/maintenance-tasks',
        operationId: 'maintenanceTaskStore',
        tags: ['Maintenance Task'],
        security: [['bearerSanctum' => []]],
        summary: 'Buat tugas pemeliharaan (khusus teknik)',
        description: 'assigned_to harus user dengan role mekanik. Status awal otomatis "assigned".',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['asset_id', 'assigned_to', 'tanggal_tugas'],
                properties: [
                    new OA\Property(property: 'asset_id', type: 'integer', example: 1),
                    new OA\Property(property: 'assigned_to', type: 'integer', example: 2),
                    new OA\Property(property: 'tanggal_tugas', type: 'string', format: 'date', example: '2026-06-12'),
                    new OA\Property(property: 'target_jam_jalan', type: 'integer', nullable: true, example: 2000),
                    new OA\Property(property: 'catatan_tugas', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tugas pemeliharaan berhasil dibuat'),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'assigned_to' => [
                'required',
                'exists:users,id',
                Rule::exists('users', 'id')->where('role', 'mekanik'),
            ],
            'tanggal_tugas' => ['required', 'date'],
            'target_jam_jalan' => ['nullable', 'integer', 'min:0'],
            'catatan_tugas' => ['nullable', 'string'],
        ]);

        $task = MaintenanceTask::create([
            'asset_id' => $validated['asset_id'],
            'assigned_by' => Auth::id(),
            'assigned_to' => $validated['assigned_to'],
            'tanggal_tugas' => $validated['tanggal_tugas'],
            'target_jam_jalan' => $validated['target_jam_jalan'] ?? null,
            'catatan_tugas' => $validated['catatan_tugas'] ?? null,
            'status' => 'assigned',
        ]);

        $task->load(['asset', 'assignedBy', 'assignedTo']);

        return response()->json([
            'message' => 'Tugas pemeliharaan berhasil dibuat',
            'data' => $task
        ], 201);
    }

    #[OA\Get(
        path: '/api/maintenance-tasks/{id}',
        operationId: 'maintenanceTaskShow',
        tags: ['Maintenance Task'],
        security: [['bearerSanctum' => []]],
        summary: 'Detail tugas pemeliharaan',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Detail tugas pemeliharaan berhasil diambil'),
            new OA\Response(response: 403, description: 'Bukan tugas Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
        ]
    )]
    public function show($id)
    {
        $task = MaintenanceTask::with(['asset', 'assignedBy', 'assignedTo'])
            ->findOrFail($id);

        if (Auth::user()->role === 'mekanik' && $task->assigned_to !== Auth::id()) {
            return response()->json([
                'message' => 'Forbidden. Tugas ini bukan milik Anda.'
            ], 403);
        }

        return response()->json([
            'message' => 'Detail tugas pemeliharaan berhasil diambil',
            'data' => $task
        ]);
    }

    #[OA\Put(
        path: '/api/maintenance-tasks/{id}',
        operationId: 'maintenanceTaskUpdate',
        tags: ['Maintenance Task'],
        security: [['bearerSanctum' => []]],
        summary: 'Perbarui tugas pemeliharaan (khusus teknik)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['asset_id', 'assigned_to', 'tanggal_tugas'],
                properties: [
                    new OA\Property(property: 'asset_id', type: 'integer', example: 1),
                    new OA\Property(property: 'assigned_to', type: 'integer', example: 2),
                    new OA\Property(property: 'tanggal_tugas', type: 'string', format: 'date', example: '2026-06-12'),
                    new OA\Property(property: 'target_jam_jalan', type: 'integer', nullable: true),
                    new OA\Property(property: 'catatan_tugas', type: 'string', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['assigned', 'in_progress', 'submitted', 'completed', 'cancelled'], nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tugas pemeliharaan berhasil diperbarui'),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(Request $request, $id)
    {
        $task = MaintenanceTask::findOrFail($id);

        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'assigned_to' => [
                'required',
                'exists:users,id',
                Rule::exists('users', 'id')->where('role', 'mekanik'),
            ],
            'tanggal_tugas' => ['required', 'date'],
            'target_jam_jalan' => ['nullable', 'integer', 'min:0'],
            'catatan_tugas' => ['nullable', 'string'],
            'status' => [
                'nullable',
                Rule::in(['assigned', 'in_progress', 'submitted', 'completed', 'cancelled']),
            ],
        ]);

        $task->update([
            'asset_id' => $validated['asset_id'],
            'assigned_to' => $validated['assigned_to'],
            'tanggal_tugas' => $validated['tanggal_tugas'],
            'target_jam_jalan' => $validated['target_jam_jalan'] ?? null,
            'catatan_tugas' => $validated['catatan_tugas'] ?? null,
            'status' => $validated['status'] ?? $task->status,
        ]);

        $task->load(['asset', 'assignedBy', 'assignedTo']);

        return response()->json([
            'message' => 'Tugas pemeliharaan berhasil diperbarui',
            'data' => $task
        ]);
    }

    #[OA\Patch(
        path: '/api/maintenance-tasks/{id}/status',
        operationId: 'maintenanceTaskUpdateStatus',
        tags: ['Maintenance Task'],
        security: [['bearerSanctum' => []]],
        summary: 'Ubah status tugas pemeliharaan',
        description: 'Mekanik tidak dapat menyetel status ke completed/cancelled.',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['assigned', 'in_progress', 'submitted', 'completed', 'cancelled'], example: 'in_progress'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Status tugas pemeliharaan berhasil diperbarui'),
            new OA\Response(response: 403, description: 'Tidak diizinkan', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function updateStatus(Request $request, $id)
    {
        $task = MaintenanceTask::findOrFail($id);

        if (Auth::user()->role === 'mekanik' && $task->assigned_to !== Auth::id()) {
            return response()->json([
                'message' => 'Forbidden. Tugas ini bukan milik Anda.'
            ], 403);
        }

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['assigned', 'in_progress', 'submitted', 'completed', 'cancelled']),
            ],
        ]);

        if (Auth::user()->role === 'mekanik' && in_array($validated['status'], ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Mekanik tidak dapat menyelesaikan atau membatalkan tugas secara langsung.'
            ], 403);
        }

        $task->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Status tugas pemeliharaan berhasil diperbarui',
            'data' => $task
        ]);
    }

    #[OA\Delete(
        path: '/api/maintenance-tasks/{id}',
        operationId: 'maintenanceTaskDestroy',
        tags: ['Maintenance Task'],
        security: [['bearerSanctum' => []]],
        summary: 'Hapus tugas pemeliharaan (khusus teknik)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Tugas pemeliharaan berhasil dihapus', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 404, description: 'Tugas tidak ditemukan'),
        ]
    )]
    public function destroy($id)
    {
        $task = MaintenanceTask::findOrFail($id);
        $task->delete();

        return response()->json([
            'message' => 'Tugas pemeliharaan berhasil dihapus'
        ]);
    }

    #[OA\Get(
        path: '/api/maintenance-due-assets',
        operationId: 'maintenanceDueAssets',
        tags: ['Maintenance Task'],
        security: [['bearerSanctum' => []]],
        summary: 'Daftar aset yang jatuh tempo pemeliharaan (khusus teknik)',
        description: 'Membandingkan selisih jam jalan terbaru dengan jam jalan saat pemeliharaan terakhir terhadap interval pemeliharaan aset.',
        responses: [new OA\Response(response: 200, description: 'Data aset yang perlu maintenance berhasil diambil')]
    )]
    public function dueAssets()
    {
        $assets = Asset::with('operatingHours')->get();

        $dueAssets = $assets->map(function ($asset) {
            $interval = (int) $asset->maintenance_interval_hours;
            if ($interval <= 0) return null;

            // Total jam jalan = sum of all operating hour entries
            $totalJamJalan = $asset->operatingHours->sum('jam_jalan');
            if ($totalJamJalan < $interval) return null;

            // Current maintenance target (e.g. 250, 500, 750...)
            $currentTarget = (int) floor($totalJamJalan / $interval) * $interval;

            // Already done if there is a completed/submitted task covering this target
            $alreadyDone = MaintenanceTask::where('asset_id', $asset->id)
                ->whereIn('status', ['completed', 'submitted'])
                ->where('target_jam_jalan', '>=', $currentTarget)
                ->exists();

            if ($alreadyDone) return null;

            return [
                'asset_id'          => $asset->id,
                'nomor_peralatan'   => $asset->nomor_peralatan,
                'nama_mesin'        => $asset->nama_mesin,
                'area_mesin'        => $asset->area_mesin,
                'jam_jalan_terakhir' => $totalJamJalan,
                'interval'          => $interval,
                'selisih_jam'       => $totalJamJalan - ($currentTarget - $interval),
                'status'            => 'due_maintenance',
            ];
        })->filter()->values();

        return response()->json([
            'message' => 'Data aset yang perlu maintenance berhasil diambil',
            'data' => $dueAssets
        ]);
    }
}