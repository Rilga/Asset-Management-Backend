<?php

namespace App\Http\Controllers\Api\Teknik;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class MekanikController extends Controller
{
    #[OA\Get(
        path: '/api/mekanik',
        operationId: 'mekanikIndex',
        tags: ['Mekanik - Akun'],
        security: [['bearerSanctum' => []]],
        summary: 'Daftar akun mekanik (khusus teknik)',
        responses: [new OA\Response(response: 200, description: 'Data mekanik berhasil diambil')]
    )]
    public function index()
    {
        $mekanik = User::where('role', 'mekanik')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Data mekanik berhasil diambil',
            'data' => $mekanik
        ]);
    }

    #[OA\Post(
        path: '/api/mekanik',
        operationId: 'mekanikStore',
        tags: ['Mekanik - Akun'],
        security: [['bearerSanctum' => []]],
        summary: 'Buat akun mekanik (khusus teknik)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'username', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Andi Mekanik'),
                    new OA\Property(property: 'username', type: 'string', example: 'mekanik_02'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'mekanik02@pabrik.com'),
                    new OA\Property(property: 'no_telp', type: 'string', nullable: true, example: '08123456789'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Data mekanik berhasil dibuat'),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $mekanik = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'no_telp' => $validated['no_telp'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'mekanik',
        ]);

        return response()->json([
            'message' => 'Data mekanik berhasil dibuat',
            'data' => $mekanik
        ], 201);
    }

    #[OA\Get(
        path: '/api/mekanik/{id}',
        operationId: 'mekanikShow',
        tags: ['Mekanik - Akun'],
        security: [['bearerSanctum' => []]],
        summary: 'Detail akun mekanik',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Detail mekanik berhasil diambil'),
            new OA\Response(response: 404, description: 'Mekanik tidak ditemukan'),
        ]
    )]
    public function show($id)
    {
        $mekanik = User::where('role', 'mekanik')->findOrFail($id);

        return response()->json([
            'message' => 'Detail mekanik berhasil diambil',
            'data' => $mekanik
        ]);
    }

    #[OA\Put(
        path: '/api/mekanik/{id}',
        operationId: 'mekanikUpdate',
        tags: ['Mekanik - Akun'],
        security: [['bearerSanctum' => []]],
        summary: 'Perbarui akun mekanik',
        description: 'Password opsional; kosongkan untuk mempertahankan password lama.',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'username', 'email'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Andi Mekanik'),
                    new OA\Property(property: 'username', type: 'string', example: 'mekanik_02'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'mekanik02@pabrik.com'),
                    new OA\Property(property: 'no_telp', type: 'string', nullable: true),
                    new OA\Property(property: 'password', type: 'string', format: 'password', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Data mekanik berhasil diperbarui'),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(Request $request, $id)
    {
        $mekanik = User::where('role', 'mekanik')->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($mekanik->id),
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($mekanik->id),
            ],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $mekanik->name = $validated['name'];
        $mekanik->username = $validated['username'];
        $mekanik->email = $validated['email'];
        $mekanik->no_telp = $validated['no_telp'] ?? null;

        if (!empty($validated['password'])) {
            $mekanik->password = Hash::make($validated['password']);
        }

        $mekanik->save();

        return response()->json([
            'message' => 'Data mekanik berhasil diperbarui',
            'data' => $mekanik
        ]);
    }

    #[OA\Delete(
        path: '/api/mekanik/{id}',
        operationId: 'mekanikDestroy',
        tags: ['Mekanik - Akun'],
        security: [['bearerSanctum' => []]],
        summary: 'Hapus akun mekanik',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Data mekanik berhasil dihapus', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 404, description: 'Mekanik tidak ditemukan'),
        ]
    )]
    public function destroy($id)
    {
        $mekanik = User::where('role', 'mekanik')->findOrFail($id);
        $mekanik->delete();

        return response()->json([
            'message' => 'Data mekanik berhasil dihapus'
        ]);
    }
}
