<?php

namespace App\Services\Rag;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiChatService
{
    protected string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.python_ai.base_url'), '/');
        $this->apiKey = config('services.python_ai.api_key');
    }

    /**
     * Panggil endpoint chat AI Engine (/api/v1/query/chat).
     *
     * @param array $payload Sesuai kontrak ChatRequest AI Engine.
     * @return array Response: success, session_id, reply, sources, quick_replies, kg_context, latency_ms
     */
    public function chat(array $payload): array
    {
        $response = Http::timeout(120)
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->post($this->baseUrl . '/api/v1/query/chat', $payload);

        if (!$response->successful()) {
            throw new RuntimeException('AI Engine chat gagal: ' . $response->body());
        }

        return $response->json();
    }
}
