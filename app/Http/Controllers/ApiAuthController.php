<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class ApiAuthController extends Controller
{
    #[OA\Post(
        path: '/api/auth/login',
        operationId: 'authLogin',
        tags: ['Auth'],
        summary: 'Login & terbitkan token Sanctum',
        description: 'Autentikasi dengan username & password. Mengembalikan Personal Access Token (Bearer). Token lama otomatis dihapus.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'teknik_01'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Login berhasil.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'user', type: 'string', example: 'Budi Teknik'),
                                new OA\Property(property: 'role', type: 'string', example: 'teknik'),
                                new OA\Property(property: 'access_token', type: 'string', example: '1|abcdef...'),
                                new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Kredensial salah / validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function login(Request $request)
    {
        // Validasi Input
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Cek Kredensial
        if (!Auth::attempt($request->only('username', 'password'))) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password salah.'],
            ]);
        }

        $user = Auth::user();

        // Opsional: Hapus token lama agar tabel tidak penuh
        $user->tokens()->delete();

        // Terbitkan Personal Access Token
        $token = $user->createToken('API Token untuk ' . $user->username)->plainTextToken;

        // Kembalikan Response
        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'data' => [
                'user' => $user->name,
                'role' => $user->role, // Kolom yang kita buat di awal
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 200);
    }
}