<?php

namespace App\Http\Controllers\Api\Knowledge;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeDocument;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class KnowledgeDocumentController extends Controller
{
    #[OA\Get(
        path: '/api/knowledge-documents',
        operationId: 'knowledgeDocumentIndex',
        tags: ['Knowledge'],
        security: [['bearerSanctum' => []]],
        summary: 'Daftar knowledge document',
        responses: [new OA\Response(response: 200, description: 'Data knowledge document berhasil diambil')]
    )]
    public function index()
    {
        $documents = KnowledgeDocument::with('asset')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Data knowledge document berhasil diambil',
            'data' => $documents,
        ]);
    }

    #[OA\Get(
        path: '/api/knowledge-documents/{id}',
        operationId: 'knowledgeDocumentShow',
        tags: ['Knowledge'],
        security: [['bearerSanctum' => []]],
        summary: 'Detail knowledge document (beserta chunks)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Detail knowledge document berhasil diambil'),
            new OA\Response(response: 404, description: 'Tidak ditemukan'),
        ]
    )]
    public function show($id)
    {
        $document = KnowledgeDocument::with([
            'asset',
            'chunks',
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Detail knowledge document berhasil diambil',
            'data' => $document,
        ]);
    }

    #[OA\Delete(
        path: '/api/knowledge-documents/{id}',
        operationId: 'knowledgeDocumentDestroy',
        tags: ['Knowledge'],
        security: [['bearerSanctum' => []]],
        summary: 'Hapus knowledge document (khusus teknik)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Knowledge document berhasil dihapus', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 404, description: 'Tidak ditemukan'),
        ]
    )]
    public function destroy($id)
    {
        $document = KnowledgeDocument::findOrFail($id);

        $document->delete();

        return response()->json([
            'message' => 'Knowledge document berhasil dihapus',
        ]);
    }
}