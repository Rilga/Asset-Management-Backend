<?php

namespace App\Services\Rag;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class EmbeddingService
{
    protected string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.python_ai.base_url'), '/');
        $this->apiKey = config('services.python_ai.api_key');
    }

    public function embedTexts(array $texts): array
    {
        $response = Http::timeout(120)
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->post($this->baseUrl . '/api/v1/embed', [
                'texts' => $texts,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Gagal generate embedding: ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['embeddings']) || !is_array($data['embeddings'])) {
            throw new RuntimeException('Response embedding tidak valid.');
        }

        return $data['embeddings'];
    }

    public function embedQuery(string $query): array
    {
        $response = Http::timeout(120)
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->post($this->baseUrl . '/api/v1/embed/query', [
                'query' => $query,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Gagal generate query embedding: ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['embedding']) || !is_array($data['embedding'])) {
            throw new RuntimeException('Response query embedding tidak valid.');
        }

        return $data['embedding'];
    }
}