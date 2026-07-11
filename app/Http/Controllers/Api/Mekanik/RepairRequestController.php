<?php

namespace App\Http\Controllers\Api\Mekanik;

use App\Http\Controllers\Controller;
use App\Models\RepairRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class RepairRequestController extends Controller
{
    #[OA\Get(
        path: '/api/repair-requests',
        operationId: 'repairRequestIndex',
        tags: ['Repair Request'],
        security: [['bearerSanctum' => []]],
        summary: 'Daftar pengajuan perbaikan',
        description: 'Mekanik hanya melihat pengajuan miliknya sendiri.',
        responses: [new OA\Response(response: 200, description: 'Data pengajuan perbaikan berhasil diambil')]
    )]
    public function index()
    {
        $query = RepairRequest::with([
            'asset',
            'mechanic',
            'verifiedBy',
        ])->latest();

        if (Auth::user()->role === 'mekanik') {
            $query->where('mechanic_id', Auth::id());
        }

        return response()->json([
            'message' => 'Data pengajuan perbaikan berhasil diambil',
            'data' => $query->get(),
        ]);
    }

    #[OA\Post(
        path: '/api/repair-requests',
        operationId: 'repairRequestStore',
        tags: ['Repair Request'],
        security: [['bearerSanctum' => []]],
        summary: 'Buat pengajuan perbaikan (khusus mekanik)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['asset_id', 'kondisi_perbaikan', 'catatan_kerusakan'],
                    properties: [
                        new OA\Property(property: 'asset_id', type: 'integer', example: 1),
                        new OA\Property(property: 'kondisi_perbaikan', type: 'string', enum: ['ringan', 'sedang', 'berat'], example: 'sedang'),
                        new OA\Property(property: 'catatan_kerusakan', type: 'string', example: 'Seal bocor pada silinder'),
                        new OA\Property(property: 'bukti_foto', type: 'string', format: 'binary', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Pengajuan perbaikan berhasil dibuat'),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'kondisi_perbaikan' => [
                'required',
                Rule::in(['ringan', 'sedang', 'berat']),
            ],
            'catatan_kerusakan' => ['required', 'string'],
            'bukti_foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $buktiFotoPath = null;

        if ($request->hasFile('bukti_foto')) {
            $buktiFotoPath = $request->file('bukti_foto')
                ->store('repair-requests/bukti-foto', 'public');
        }

        $repairRequest = RepairRequest::create([
            'asset_id' => $validated['asset_id'],
            'mechanic_id' => Auth::id(),
            'kondisi_perbaikan' => $validated['kondisi_perbaikan'],
            'catatan_kerusakan' => $validated['catatan_kerusakan'],
            'bukti_foto' => $buktiFotoPath,
            'status_verifikasi' => 'pending',
        ]);

        $repairRequest->load(['asset', 'mechanic', 'verifiedBy']);

        return response()->json([
            'message' => 'Pengajuan perbaikan berhasil dibuat',
            'data' => $repairRequest,
        ], 201);
    }

    #[OA\Get(
        path: '/api/repair-requests/{id}',
        operationId: 'repairRequestShow',
        tags: ['Repair Request'],
        security: [['bearerSanctum' => []]],
        summary: 'Detail pengajuan perbaikan',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Detail pengajuan perbaikan berhasil diambil'),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
        ]
    )]
    public function show($id)
    {
        $repairRequest = RepairRequest::with([
            'asset',
            'mechanic',
            'verifiedBy',
        ])->findOrFail($id);

        if (
            Auth::user()->role === 'mekanik' &&
            $repairRequest->mechanic_id !== Auth::id()
        ) {
            return response()->json([
                'message' => 'Forbidden. Pengajuan ini bukan milik Anda.',
            ], 403);
        }

        return response()->json([
            'message' => 'Detail pengajuan perbaikan berhasil diambil',
            'data' => $repairRequest,
        ]);
    }

    #[OA\Put(
        path: '/api/repair-requests/{id}',
        operationId: 'repairRequestUpdate',
        tags: ['Repair Request'],
        security: [['bearerSanctum' => []]],
        summary: 'Perbarui pengajuan perbaikan (hanya saat status pending)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['asset_id', 'kondisi_perbaikan', 'catatan_kerusakan'],
                    properties: [
                        new OA\Property(property: 'asset_id', type: 'integer', example: 1),
                        new OA\Property(property: 'kondisi_perbaikan', type: 'string', enum: ['ringan', 'sedang', 'berat'], example: 'berat'),
                        new OA\Property(property: 'catatan_kerusakan', type: 'string'),
                        new OA\Property(property: 'bukti_foto', type: 'string', format: 'binary', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pengajuan perbaikan berhasil diperbarui'),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 422, description: 'Sudah diverifikasi / validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(Request $request, $id)
    {
        $repairRequest = RepairRequest::findOrFail($id);

        if ($repairRequest->mechanic_id !== Auth::id()) {
            return response()->json([
                'message' => 'Forbidden. Pengajuan ini bukan milik Anda.',
            ], 403);
        }

        if ($repairRequest->status_verifikasi !== 'pending') {
            return response()->json([
                'message' => 'Pengajuan yang sudah diverifikasi tidak dapat diubah.',
            ], 422);
        }

        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'kondisi_perbaikan' => [
                'required',
                Rule::in(['ringan', 'sedang', 'berat']),
            ],
            'catatan_kerusakan' => ['required', 'string'],
            'bukti_foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('bukti_foto')) {
            if ($repairRequest->bukti_foto) {
                Storage::disk('public')->delete($repairRequest->bukti_foto);
            }

            $validated['bukti_foto'] = $request->file('bukti_foto')
                ->store('repair-requests/bukti-foto', 'public');
        }

        $repairRequest->update($validated);

        $repairRequest->load(['asset', 'mechanic', 'verifiedBy']);

        return response()->json([
            'message' => 'Pengajuan perbaikan berhasil diperbarui',
            'data' => $repairRequest,
        ]);
    }

    #[OA\Patch(
        path: '/api/repair-requests/{id}/verify',
        operationId: 'repairRequestVerify',
        tags: ['Repair Request'],
        security: [['bearerSanctum' => []]],
        summary: 'Verifikasi pengajuan perbaikan (khusus teknik)',
        description: 'Hanya pengajuan berstatus pending yang bisa diverifikasi menjadi approved/rejected.',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status_verifikasi'],
                properties: [
                    new OA\Property(property: 'status_verifikasi', type: 'string', enum: ['approved', 'rejected'], example: 'approved'),
                    new OA\Property(property: 'catatan_verifikasi', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Status pengajuan perbaikan berhasil diverifikasi'),
            new OA\Response(response: 422, description: 'Sudah diverifikasi / validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function verify(Request $request, $id)
    {
        $repairRequest = RepairRequest::findOrFail($id);

        $validated = $request->validate([
            'status_verifikasi' => [
                'required',
                Rule::in(['approved', 'rejected']),
            ],
            'catatan_verifikasi' => ['nullable', 'string'],
        ]);

        if ($repairRequest->status_verifikasi !== 'pending') {
            return response()->json([
                'message' => 'Pengajuan ini sudah diverifikasi.',
            ], 422);
        }

        $repairRequest->update([
            'status_verifikasi' => $validated['status_verifikasi'],
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'catatan_verifikasi' => $validated['catatan_verifikasi'] ?? null,
        ]);

        $repairRequest->load(['asset', 'mechanic', 'verifiedBy']);

        return response()->json([
            'message' => 'Status pengajuan perbaikan berhasil diverifikasi',
            'data' => $repairRequest,
        ]);
    }

    #[OA\Delete(
        path: '/api/repair-requests/{id}',
        operationId: 'repairRequestDestroy',
        tags: ['Repair Request'],
        security: [['bearerSanctum' => []]],
        summary: 'Hapus pengajuan perbaikan (hanya saat status pending)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Pengajuan perbaikan berhasil dihapus', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 422, description: 'Sudah diverifikasi'),
        ]
    )]
    public function destroy($id)
    {
        $repairRequest = RepairRequest::findOrFail($id);

        if ($repairRequest->mechanic_id !== Auth::id()) {
            return response()->json([
                'message' => 'Forbidden. Pengajuan ini bukan milik Anda.',
            ], 403);
        }

        if ($repairRequest->status_verifikasi !== 'pending') {
            return response()->json([
                'message' => 'Pengajuan yang sudah diverifikasi tidak dapat dihapus.',
            ], 422);
        }

        if ($repairRequest->bukti_foto) {
            Storage::disk('public')->delete($repairRequest->bukti_foto);
        }

        $repairRequest->delete();

        return response()->json([
            'message' => 'Pengajuan perbaikan berhasil dihapus',
        ]);
    }
}