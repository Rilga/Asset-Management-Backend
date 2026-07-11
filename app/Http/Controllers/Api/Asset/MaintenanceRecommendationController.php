<?php

namespace App\Http\Controllers\Api\Asset;

use App\Http\Controllers\Controller;
use App\Services\Rag\MaintenanceRecommendationService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MaintenanceRecommendationController extends Controller
{
    public function __construct(
        protected MaintenanceRecommendationService $service
    ) {
    }

    /**
     * GET /assets/{assetId}/recommendation
     * Menghasilkan rekomendasi pemeliharaan terstruktur dari AI Engine.
     */
    #[OA\Get(
        path: '/api/assets/{assetId}/recommendation',
        operationId: 'assetRecommendation',
        tags: ['Recommendation'],
        security: [['bearerSanctum' => []]],
        summary: 'Rekomendasi pemeliharaan aset (AI Engine)',
        description: 'Menyusun konteks aset (jam operasi, histori, RAG chunks) lalu memanggil AI Engine /query/maintenance-recommendation (LLM + Knowledge Graph). Mengembalikan rekomendasi terstruktur per kategori.',
        parameters: [
            new OA\Parameter(name: 'assetId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 2),
            new OA\Parameter(name: 'top_k', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 10, default: 5)),
            new OA\Parameter(name: 'keluhan', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Uraian kerusakan/keluhan spesifik (untuk konteks rekomendasi perbaikan)'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Rekomendasi pemeliharaan berhasil dihasilkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Rekomendasi pemeliharaan berhasil dihasilkan'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'success', type: 'boolean', example: true),
                                new OA\Property(property: 'asset_id', type: 'integer', example: 2),
                                new OA\Property(property: 'context_summary', type: 'string'),
                                new OA\Property(
                                    property: 'categories',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'category', type: 'string', example: 'Sistem Hidrolik'),
                                            new OA\Property(property: 'priority', type: 'string', example: 'high'),
                                            new OA\Property(property: 'reasoning', type: 'string'),
                                            new OA\Property(property: 'action_items', type: 'array', items: new OA\Items(type: 'string')),
                                        ]
                                    )
                                ),
                                new OA\Property(property: 'ai_note', type: 'string'),
                                new OA\Property(property: 'special_flag', type: 'string', nullable: true, example: 'overdue'),
                                new OA\Property(property: 'kg_context', type: 'object'),
                                new OA\Property(property: 'confidence_score', type: 'number', format: 'float', example: 0.82),
                                new OA\Property(property: 'llm_model_used', type: 'string', example: 'llama-3.3-70b-versatile'),
                                new OA\Property(property: 'latency_ms', type: 'integer', example: 1450),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Aset tidak ditemukan'),
            new OA\Response(response: 500, description: 'AI Engine error'),
        ]
    )]
    public function show(Request $request, int $assetId)
    {
        $topK = (int) $request->query('top_k', 5);
        $topK = max(1, min($topK, 10));

        $keluhan = $request->query('keluhan');

        $result = $this->service->recommend($assetId, $topK, $keluhan ?: null);

        return response()->json([
            'message' => 'Rekomendasi pemeliharaan berhasil dihasilkan',
            'data' => $result,
        ]);
    }
}
