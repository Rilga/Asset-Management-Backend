<?php

namespace App\Services\Rag;

class RagService
{
    public function __construct(
        protected EmbeddingService $embeddingService,
        protected VectorSearchService $vectorSearchService,
        protected LlmService $llmService,
    ) {
    }

    /**
     * Embed query + vector search saja (tanpa LLM).
     * Dipakai untuk mengambil konteks RAG yang akan dikirim ke AI Engine.
     *
     * @return array{contexts: array, retrieval_time_ms: int}
     */
    public function retrieve(string $question, ?int $assetId = null, int $topK = 5): array
    {
        $queryEmbedding = $this->embeddingService->embedQuery($question);

        $retrievalStart = microtime(true);

        $contexts = $this->vectorSearchService->search(
            queryEmbedding: $queryEmbedding,
            assetId: $assetId,
            topK: $topK
        );

        $retrievalTimeMs = (int) ((microtime(true) - $retrievalStart) * 1000);

        return [
            'contexts' => $contexts,
            'retrieval_time_ms' => $retrievalTimeMs,
        ];
    }

    /**
     * Alur RAG lama dengan LlmService stub.
     * Dipertahankan untuk kompatibilitas; chatbot baru memakai retrieve() + AiChatService.
     */
    public function ask(string $question, ?int $assetId = null, int $topK = 5): array
    {
        $startTime = microtime(true);

        $retrieval = $this->retrieve($question, $assetId, $topK);

        $answer = $this->llmService->generateAnswer($question, $retrieval['contexts']);

        $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

        return [
            'answer' => $answer,
            'contexts' => $retrieval['contexts'],
            'retrieval_time_ms' => $retrieval['retrieval_time_ms'],
            'response_time_ms' => $responseTimeMs,
        ];
    }
}