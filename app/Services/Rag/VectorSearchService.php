<?php

namespace App\Services\Rag;

use Illuminate\Support\Facades\DB;

class VectorSearchService
{
    public function search(array $queryEmbedding, ?int $assetId = null, int $topK = 5): array
    {
        $vector = $this->vectorToSql($queryEmbedding);

        if ($assetId) {
            $results = DB::select(
                "
                SELECT
                    id,
                    knowledge_document_id,
                    asset_id,
                    chunk_text,
                    metadata,
                    (embedding <=> ?::vector) AS distance
                FROM knowledge_chunks
                WHERE embedding IS NOT NULL
                AND asset_id = ?
                ORDER BY embedding <=> ?::vector
                LIMIT ?
                ",
                [
                    $vector,
                    $assetId,
                    $vector,
                    $topK,
                ]
            );
        } else {
            $results = DB::select(
                "
                SELECT
                    id,
                    knowledge_document_id,
                    asset_id,
                    chunk_text,
                    metadata,
                    (embedding <=> ?::vector) AS distance
                FROM knowledge_chunks
                WHERE embedding IS NOT NULL
                ORDER BY embedding <=> ?::vector
                LIMIT ?
                ",
                [
                    $vector,
                    $vector,
                    $topK,
                ]
            );
        }

        return collect($results)->map(function ($row) {
            $metadata = is_string($row->metadata)
                ? json_decode($row->metadata, true)
                : $row->metadata;

            return [
                'chunk_id' => $row->id,
                'knowledge_document_id' => $row->knowledge_document_id,
                'asset_id' => $row->asset_id,
                'chunk_text' => $row->chunk_text,
                'metadata' => $metadata,
                'distance' => (float) $row->distance,
                'score' => 1 - (float) $row->distance,
            ];
        })->toArray();
    }

    private function vectorToSql(array $vector): string
    {
        return '[' . implode(',', array_map('floatval', $vector)) . ']';
    }
}