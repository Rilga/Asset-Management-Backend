<?php

namespace App\Http\Controllers\Api\Evaluation;

use App\Http\Controllers\Controller;
use App\Models\RetrievalEvaluation;
use App\Services\Rag\EmbeddingService;
use App\Services\Rag\VectorSearchService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class RetrievalEvaluationController extends Controller
{
    public function __construct(
        protected EmbeddingService $embeddingService,
        protected VectorSearchService $vectorSearchService,
    ) {
    }

    #[OA\Get(
        path: '/api/retrieval-evaluations',
        operationId: 'retrievalEvaluationIndex',
        tags: ['Evaluation'],
        security: [['bearerSanctum' => []]],
        summary: 'Daftar evaluasi retrieval (khusus teknik)',
        responses: [new OA\Response(response: 200, description: 'Data evaluasi retrieval berhasil diambil')]
    )]
    public function index()
    {
        $evaluations = RetrievalEvaluation::with('asset')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Data evaluasi retrieval berhasil diambil',
            'data' => $evaluations,
        ]);
    }

    #[OA\Get(
        path: '/api/retrieval-evaluations/{id}',
        operationId: 'retrievalEvaluationShow',
        tags: ['Evaluation'],
        security: [['bearerSanctum' => []]],
        summary: 'Detail evaluasi retrieval',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Detail evaluasi retrieval berhasil diambil'),
            new OA\Response(response: 404, description: 'Tidak ditemukan'),
        ]
    )]
    public function show($id)
    {
        $evaluation = RetrievalEvaluation::with('asset')->findOrFail($id);

        return response()->json([
            'message' => 'Detail evaluasi retrieval berhasil diambil',
            'data' => $evaluation,
        ]);
    }

    #[OA\Post(
        path: '/api/retrieval-evaluations/evaluate',
        operationId: 'retrievalEvaluationEvaluate',
        tags: ['Evaluation'],
        security: [['bearerSanctum' => []]],
        summary: 'Jalankan evaluasi retrieval (khusus teknik)',
        description: 'Menghitung context_precision (relevan/total) dan context_recall (biner: 1 jika ada hasil relevan).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['question', 'expected_source_type'],
                properties: [
                    new OA\Property(property: 'question', type: 'string', example: 'Cara mengganti seal hidrolik?'),
                    new OA\Property(property: 'asset_id', type: 'integer', nullable: true, example: 2),
                    new OA\Property(property: 'expected_source_type', type: 'string', example: 'manual_book'),
                    new OA\Property(property: 'expected_source_id', type: 'integer', nullable: true, example: 2),
                    new OA\Property(property: 'top_k', type: 'integer', nullable: true, minimum: 1, maximum: 10, example: 5),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Evaluasi retrieval berhasil dijalankan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'evaluation', type: 'object'),
                                new OA\Property(
                                    property: 'metrics',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'context_precision', type: 'number', format: 'float', example: 0.6),
                                        new OA\Property(property: 'context_recall', type: 'number', format: 'float', example: 1),
                                        new OA\Property(property: 'retrieval_time_ms', type: 'integer', example: 85),
                                        new OA\Property(property: 'top_k', type: 'integer', example: 5),
                                        new OA\Property(property: 'relevant_results_count', type: 'integer', example: 3),
                                    ]
                                ),
                                new OA\Property(property: 'retrieved_results', type: 'array', items: new OA\Items(type: 'object')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function evaluate(Request $request)
    {
        $validated = $request->validate([
            'question' => ['required', 'string'],
            'asset_id' => ['nullable', 'exists:assets,id'],
            'expected_source_type' => ['required', 'string'],
            'expected_source_id' => ['nullable', 'integer'],
            'top_k' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $topK = $validated['top_k'] ?? 5;
        $assetId = $validated['asset_id'] ?? null;

        $start = microtime(true);

        $queryEmbedding = $this->embeddingService->embedQuery($validated['question']);

        $results = $this->vectorSearchService->search(
            queryEmbedding: $queryEmbedding,
            assetId: $assetId,
            topK: $topK
        );

        $retrievalTimeMs = (int) ((microtime(true) - $start) * 1000);

        $relevantResults = collect($results)->filter(function ($result) use ($validated) {
            $metadata = $result['metadata'] ?? [];

            $sameType = ($metadata['source_type'] ?? null) === $validated['expected_source_type'];

            if (!empty($validated['expected_source_id'])) {
                return $sameType && (int) ($metadata['source_id'] ?? 0) === (int) $validated['expected_source_id'];
            }

            return $sameType;
        });

        $relevantCount = $relevantResults->count();

        $contextPrecision = count($results) > 0
            ? $relevantCount / count($results)
            : 0;

        $contextRecall = $relevantCount > 0 ? 1 : 0;

        $evaluation = RetrievalEvaluation::create([
            'asset_id' => $assetId,
            'question' => $validated['question'],
            'expected_source_type' => $validated['expected_source_type'],
            'expected_source_id' => $validated['expected_source_id'] ?? null,
            'retrieved_results' => $results,
            'top_k' => $topK,
            'context_precision' => round($contextPrecision, 2),
            'context_recall' => round($contextRecall, 2),
            'retrieval_time_ms' => $retrievalTimeMs,
        ]);

        return response()->json([
            'message' => 'Evaluasi retrieval berhasil dijalankan',
            'data' => [
                'evaluation' => $evaluation,
                'metrics' => [
                    'context_precision' => round($contextPrecision, 2),
                    'context_recall' => round($contextRecall, 2),
                    'retrieval_time_ms' => $retrievalTimeMs,
                    'top_k' => $topK,
                    'relevant_results_count' => $relevantCount,
                ],
                'retrieved_results' => $results,
            ],
        ], 201);
    }

    #[OA\Delete(
        path: '/api/retrieval-evaluations/{id}',
        operationId: 'retrievalEvaluationDestroy',
        tags: ['Evaluation'],
        security: [['bearerSanctum' => []]],
        summary: 'Hapus evaluasi retrieval (khusus teknik)',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Evaluasi retrieval berhasil dihapus', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 404, description: 'Tidak ditemukan'),
        ]
    )]
    public function destroy($id)
    {
        $evaluation = RetrievalEvaluation::findOrFail($id);
        $evaluation->delete();

        return response()->json([
            'message' => 'Evaluasi retrieval berhasil dihapus',
        ]);
    }
}