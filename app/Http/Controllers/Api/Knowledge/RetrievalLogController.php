<?php

namespace App\Http\Controllers\Api\Knowledge;

use App\Http\Controllers\Controller;
use App\Models\RetrievalLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class RetrievalLogController extends Controller
{
    #[OA\Get(
        path: '/api/retrieval-logs',
        operationId: 'retrievalLogIndex',
        tags: ['Knowledge'],
        security: [['bearerSanctum' => []]],
        summary: 'Daftar retrieval log',
        description: 'Mekanik hanya melihat log miliknya sendiri.',
        responses: [new OA\Response(response: 200, description: 'Data retrieval log berhasil diambil')]
    )]
    public function index()
    {
        $query = RetrievalLog::with(['user', 'chatHistory'])
            ->latest();

        if (Auth::user()->role === 'mekanik') {
            $query->where('user_id', Auth::id());
        }

        return response()->json([
            'message' => 'Data retrieval log berhasil diambil',
            'data' => $query->get(),
        ]);
    }

    #[OA\Get(
        path: '/api/retrieval-logs/{id}',
        operationId: 'retrievalLogShow',
        tags: ['Knowledge'],
        security: [['bearerSanctum' => []]],
        summary: 'Detail retrieval log',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Detail retrieval log berhasil diambil'),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
        ]
    )]
    public function show($id)
    {
        $log = RetrievalLog::with(['user', 'chatHistory'])->findOrFail($id);

        if (Auth::user()->role === 'mekanik' && $log->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Forbidden. Retrieval log ini bukan milik Anda.',
            ], 403);
        }

        return response()->json([
            'message' => 'Detail retrieval log berhasil diambil',
            'data' => $log,
        ]);
    }

    #[OA\Delete(
        path: '/api/retrieval-logs/{id}',
        operationId: 'retrievalLogDestroy',
        tags: ['Knowledge'],
        security: [['bearerSanctum' => []]],
        summary: 'Hapus retrieval log (khusus teknik)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Retrieval log berhasil dihapus', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 404, description: 'Tidak ditemukan'),
        ]
    )]
    public function destroy($id)
    {
        $log = RetrievalLog::findOrFail($id);

        $log->delete();

        return response()->json([
            'message' => 'Retrieval log berhasil dihapus',
        ]);
    }
}