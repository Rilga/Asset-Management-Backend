<?php

namespace App\Http\Controllers\Api\Mekanik;

use App\Http\Controllers\Controller;
use App\Models\OperatingHour;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class OperatingHourController extends Controller
{
    #[OA\Get(
        path: '/api/operating-hours',
        operationId: 'operatingHourIndex',
        tags: ['Operating Hour'],
        security: [['bearerSanctum' => []]],
        summary: 'Daftar data jam jalan',
        responses: [
            new OA\Response(response: 200, description: 'OK'),
        ]
    )]
    public function index()
    {
        $query = OperatingHour::with(['asset', 'user'])
            ->latest();

        if (Auth::user()->role === 'mekanik') {
            $query->where('user_id', Auth::id());
        }

        $operatingHours = $query->get();

        return response()->json([
            'message' => 'Data jam jalan berhasil diambil',
            'data' => $operatingHours
        ]);
    }

    #[OA\Post(
        path: '/api/operating-hours',
        operationId: 'operatingHourStore',
        tags: ['Operating Hour'],
        security: [['bearerSanctum' => []]],
        summary: 'Catat jam jalan aset (khusus mekanik)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['asset_id', 'tanggal', 'jam_jalan'],
                properties: [
                    new OA\Property(property: 'asset_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-06-10'),
                    new OA\Property(property: 'jam_jalan', type: 'integer', example: 1200),
                    new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Kondisi mesin normal'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Data jam jalan berhasil dibuat'),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'tanggal' => ['required', 'date'],
            'jam_jalan' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);

        $operatingHour = OperatingHour::create([
            'asset_id' => $asset->id,
            'user_id' => Auth::id(),
            'tanggal' => $validated['tanggal'],
            'jam_jalan' => $validated['jam_jalan'],
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        $operatingHour->load(['asset', 'user']);

        return response()->json([
            'message' => 'Data jam jalan berhasil dibuat',
            'data' => $operatingHour
        ], 201);
    }

    #[OA\Get(
        path: '/api/operating-hours/{id}',
        operationId: 'operatingHourShow',
        tags: ['Operating Hour'],
        security: [['bearerSanctum' => []]],
        summary: 'Detail jam jalan',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail jam jalan berhasil diambil'),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
        ]
    )]
    public function show($id)
    {
        $operatingHour = OperatingHour::with(['asset', 'user'])->findOrFail($id);

        if (
            Auth::user()->role === 'mekanik' &&
            $operatingHour->user_id !== Auth::id()
        ) {
            return response()->json([
                'message' => 'Forbidden. Data ini bukan milik Anda.'
            ], 403);
        }

        return response()->json([
            'message' => 'Detail jam jalan berhasil diambil',
            'data' => $operatingHour
        ]);
    }

    #[OA\Put(
        path: '/api/operating-hours/{id}',
        operationId: 'operatingHourUpdate',
        tags: ['Operating Hour'],
        security: [['bearerSanctum' => []]],
        summary: 'Perbarui jam jalan',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['asset_id', 'tanggal', 'jam_jalan'],
                properties: [
                    new OA\Property(property: 'asset_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-06-10'),
                    new OA\Property(property: 'jam_jalan', type: 'integer', example: 1300),
                    new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Kondisi mesin normal'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Data jam jalan berhasil diperbarui'),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(Request $request, $id)
    {
        $operatingHour = OperatingHour::findOrFail($id);

        if (
            Auth::user()->role === 'mekanik' &&
            $operatingHour->user_id !== Auth::id()
        ) {
            return response()->json([
                'message' => 'Forbidden. Data ini bukan milik Anda.'
            ], 403);
        }

        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'tanggal' => ['required', 'date'],
            'jam_jalan' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $operatingHour->update([
            'asset_id' => $validated['asset_id'],
            'tanggal' => $validated['tanggal'],
            'jam_jalan' => $validated['jam_jalan'],
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        $operatingHour->load(['asset', 'user']);

        return response()->json([
            'message' => 'Data jam jalan berhasil diperbarui',
            'data' => $operatingHour
        ]);
    }

    #[OA\Delete(
        path: '/api/operating-hours/{id}',
        operationId: 'operatingHourDestroy',
        tags: ['Operating Hour'],
        security: [['bearerSanctum' => []]],
        summary: 'Hapus jam jalan',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data jam jalan berhasil dihapus', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
        ]
    )]
    public function destroy($id)
    {
        $operatingHour = OperatingHour::findOrFail($id);

        if (
            Auth::user()->role === 'mekanik' &&
            $operatingHour->user_id !== Auth::id()
        ) {
            return response()->json([
                'message' => 'Forbidden. Data ini bukan milik Anda.'
            ], 403);
        }

        $operatingHour->delete();

        return response()->json([
            'message' => 'Data jam jalan berhasil dihapus'
        ]);
    }
}