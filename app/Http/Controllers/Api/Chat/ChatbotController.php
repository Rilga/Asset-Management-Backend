<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\ChatHistory;
use App\Models\RetrievalLog;
use App\Services\Rag\AiChatService;
use App\Services\Rag\RagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class ChatbotController extends Controller
{
    public function __construct(
        protected RagService $ragService,
        protected AiChatService $aiChatService,
    ) {
    }

    #[OA\Post(
        path: '/api/chat/ask',
        operationId: 'chatAsk',
        tags: ['Chatbot'],
        security: [['bearerSanctum' => []]],
        summary: 'Tanya chatbot RAG (terintegrasi AI Engine)',
        description: 'Melakukan retrieval (embed query + vector search pgvector) lalu memanggil AI Engine /query/chat (LLM Groq dengan fallback Gemini + Knowledge Graph). Menyimpan ChatHistory & RetrievalLog.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['question'],
                properties: [
                    new OA\Property(property: 'question', type: 'string', example: 'Apa penyebab tegangan genset tidak stabil?'),
                    new OA\Property(property: 'asset_id', type: 'integer', nullable: true, example: 3),
                    new OA\Property(property: 'top_k', type: 'integer', nullable: true, minimum: 1, maximum: 10, example: 5),
                    new OA\Property(property: 'session_id', type: 'string', nullable: true, example: 'sess_uji_skripsi_01'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Jawaban berhasil dihasilkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Jawaban berhasil dihasilkan'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'chat_id', type: 'integer', example: 12),
                                new OA\Property(property: 'session_id', type: 'string', example: 'sess_5_1718000000'),
                                new OA\Property(property: 'question', type: 'string'),
                                new OA\Property(property: 'answer', type: 'string'),
                                new OA\Property(property: 'sources', type: 'array', items: new OA\Items(type: 'string')),
                                new OA\Property(property: 'quick_replies', type: 'array', items: new OA\Items(type: 'string')),
                                new OA\Property(property: 'kg_context', type: 'object', nullable: true),
                                new OA\Property(property: 'contexts', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'retrieval_time_ms', type: 'integer', example: 85),
                                new OA\Property(property: 'response_time_ms', type: 'integer', example: 1450),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validasi gagal', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function ask(Request $request)
    {
        $validated = $request->validate([
            'question' => ['required', 'string'],
            'asset_id' => ['nullable', 'exists:assets,id'],
            'top_k' => ['nullable', 'integer', 'min:1', 'max:10'],
            'session_id' => ['nullable', 'string'],
        ]);

        $topK = $validated['top_k'] ?? 5;
        $assetId = $validated['asset_id'] ?? null;
        $sessionId = $validated['session_id'] ?? ('sess_' . Auth::id() . '_' . now()->timestamp);

        $startTime = microtime(true);

        // 1. Retrieval RAG (embed query + vector search di Laravel)
        $retrieval = $this->ragService->retrieve($validated['question'], $assetId, $topK);

        // 2. Bangun payload sesuai kontrak ChatRequest AI Engine
        $payload = [
            'session_id' => $sessionId,
            'asset_id' => $assetId ?? 0,
            'asset_context' => $this->buildAssetContext($assetId),
            'message' => $validated['question'],
            'retrieved_chunks' => $this->mapChunks($retrieval['contexts']),
            'chat_history' => $this->buildChatHistory($assetId),
        ];

        // 3. Panggil AI Engine (LLM asli: Groq -> fallback Gemini)
        $ai = $this->aiChatService->chat($payload);

        $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

        // 4. Simpan histori chat + retrieval log
        $chat = ChatHistory::create([
            'user_id' => Auth::id(),
            'asset_id' => $assetId,
            'session_id' => $sessionId,
            'question' => $validated['question'],
            'answer' => $ai['reply'] ?? '',
            'retrieved_context' => $retrieval['contexts'],
            'response_time_ms' => $responseTimeMs,
        ]);

        RetrievalLog::create([
            'chat_history_id' => $chat->id,
            'user_id' => Auth::id(),
            'query' => $validated['question'],
            'top_k_results' => $retrieval['contexts'],
            'top_k' => $topK,
            'retrieval_time_ms' => $retrieval['retrieval_time_ms'],
        ]);

        return response()->json([
            'message' => 'Jawaban berhasil dihasilkan',
            'data' => [
                'chat_id' => $chat->id,
                'session_id' => $sessionId,
                'question' => $validated['question'],
                'answer' => $ai['reply'] ?? '',
                'sources' => $ai['sources'] ?? [],
                'quick_replies' => $ai['quick_replies'] ?? [],
                'kg_context' => $ai['kg_context'] ?? null,
                'contexts' => $retrieval['contexts'],
                'retrieval_time_ms' => $retrieval['retrieval_time_ms'],
                'response_time_ms' => $responseTimeMs,
            ],
        ]);
    }

    /**
     * Bangun asset_context sesuai schema ChatAssetContext AI Engine.
     * total_operating_hours diambil dari jam_jalan terbaru (tabel operating_hours).
     */
    private function buildAssetContext(?int $assetId): array
    {
        if (!$assetId) {
            return [
                'nama_mesin' => 'Umum',
                'total_operating_hours' => 0,
                'maintenance_interval_hours' => 0,
            ];
        }

        $asset = Asset::with([
            'operatingHours' => fn ($q) => $q->orderByDesc('tanggal')->orderByDesc('id'),
            'maintenanceReports' => fn ($q) => $q->orderByDesc('tanggal_pemeliharaan')->orderByDesc('id'),
        ])->findOrFail($assetId);

        $latestOperatingHour = $asset->operatingHours->first();
        $lastReport = $asset->maintenanceReports->first();

        return [
            'nama_mesin' => $asset->nama_mesin,
            'total_operating_hours' => (int) ($latestOperatingHour->jam_jalan ?? 0),
            'maintenance_interval_hours' => (int) $asset->maintenance_interval_hours,
            'last_maintenance_date' => $lastReport?->tanggal_pemeliharaan,
            'last_maintenance_notes' => $lastReport?->catatan_pemeliharaan,
        ];
    }

    /**
     * Map konteks vector search ke schema ChatChunk AI Engine.
     * Metadata chunk menyimpan asset_name & source_type (bukan source_name).
     */
    private function mapChunks(array $contexts): array
    {
        return collect($contexts)->map(function ($c) {
            $metadata = $c['metadata'] ?? [];

            return [
                'chunk_text' => $c['chunk_text'] ?? '',
                'source_name' => $metadata['asset_name']
                    ?? $metadata['source_type']
                    ?? 'knowledge_base',
                'relevance_score' => $c['score'] ?? null,
            ];
        })->values()->all();
    }

    /**
     * Ambil riwayat chat sebelumnya (max 5 pasang) untuk konteks AI stateless.
     */
    private function buildChatHistory(?int $assetId): array
    {
        $history = ChatHistory::where('user_id', Auth::id())
            ->when($assetId, fn ($q) => $q->where('asset_id', $assetId))
            ->latest()
            ->take(5)
            ->get()
            ->reverse();

        $messages = [];
        foreach ($history as $h) {
            $messages[] = ['role' => 'user', 'content' => $h->question];
            $messages[] = ['role' => 'assistant', 'content' => $h->answer];
        }

        return $messages;
    }

    #[OA\Get(
        path: '/api/chat/histories',
        operationId: 'chatHistory',
        tags: ['Chatbot'],
        security: [['bearerSanctum' => []]],
        summary: 'Daftar riwayat chat (flat)',
        description: 'Mekanik hanya melihat riwayat miliknya sendiri; teknik melihat semua.',
        responses: [
            new OA\Response(response: 200, description: 'Riwayat chat berhasil diambil'),
        ]
    )]
    public function history()
    {
        $query = ChatHistory::with(['user', 'asset'])
            ->latest();

        if (Auth::user()->role === 'mekanik') {
            $query->where('user_id', Auth::id());
        }

        return response()->json([
            'message' => 'Riwayat chat berhasil diambil',
            'data' => $query->get(),
        ]);
    }

    #[OA\Get(
        path: '/api/chat/sessions',
        operationId: 'chatSessions',
        tags: ['Chatbot'],
        security: [['bearerSanctum' => []]],
        summary: 'Daftar sesi chat dikelompokkan per session_id',
        description: 'Mengembalikan sesi percakapan yang dikelompokkan. Setiap sesi berisi metadata (session_id, asset, jumlah pesan, waktu mulai/akhir) dan array messages.',
        responses: [
            new OA\Response(response: 200, description: 'Sesi chat berhasil diambil'),
        ]
    )]
    public function sessions()
    {
        $query = ChatHistory::with(['asset'])
            ->latest();

        if (Auth::user()->role === 'mekanik') {
            $query->where('user_id', Auth::id());
        }

        $chats = $query->get();

        // Kelompokkan per session_id; chat tanpa session_id masuk sesi sendiri
        $grouped = $chats->groupBy(fn ($c) => $c->session_id ?? ('orphan_' . $c->id));

        $sessions = $grouped->map(function ($items, $sessionId) {
            $first = $items->last(); // oldest message (collection is latest-first)
            $last  = $items->first();

            return [
                'session_id'   => $sessionId,
                'asset'        => $first->asset ? [
                    'id'              => $first->asset->id,
                    'nama_mesin'      => $first->asset->nama_mesin,
                    'nomor_peralatan' => $first->asset->nomor_peralatan ?? null,
                ] : null,
                'message_count' => $items->count(),
                'started_at'   => $first->created_at,
                'last_at'      => $last->created_at,
                'preview'      => $first->question,
                'messages'     => $items->sortBy('created_at')->map(fn ($c) => [
                    'id'         => $c->id,
                    'question'   => $c->question,
                    'answer'     => $c->answer,
                    'created_at' => $c->created_at,
                ])->values(),
            ];
        })->sortByDesc('last_at')->values();

        return response()->json([
            'message' => 'Sesi chat berhasil diambil',
            'data'    => $sessions,
        ]);
    }

    #[OA\Get(
        path: '/api/chat/histories/{id}',
        operationId: 'chatShow',
        tags: ['Chatbot'],
        security: [['bearerSanctum' => []]],
        summary: 'Detail riwayat chat',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail riwayat chat berhasil diambil'),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
        ]
    )]
    public function show($id)
    {
        $chat = ChatHistory::with(['user', 'asset'])->findOrFail($id);

        if (Auth::user()->role === 'mekanik' && $chat->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Forbidden. Riwayat chat ini bukan milik Anda.',
            ], 403);
        }

        return response()->json([
            'message' => 'Detail riwayat chat berhasil diambil',
            'data' => $chat,
        ]);
    }

    #[OA\Delete(
        path: '/api/chat/histories/{id}',
        operationId: 'chatDestroy',
        tags: ['Chatbot'],
        security: [['bearerSanctum' => []]],
        summary: 'Hapus riwayat chat',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Riwayat chat berhasil dihapus', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 403, description: 'Bukan milik Anda', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
        ]
    )]
    public function destroy($id)
    {
        $chat = ChatHistory::findOrFail($id);

        if (Auth::user()->role === 'mekanik' && $chat->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Forbidden. Riwayat chat ini bukan milik Anda.',
            ], 403);
        }

        $chat->delete();

        return response()->json([
            'message' => 'Riwayat chat berhasil dihapus',
        ]);
    }
}