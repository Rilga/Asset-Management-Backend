<?php

namespace App\Services\Rag;

use App\Models\Asset;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MaintenanceRecommendationService
{
    protected string $baseUrl;
    protected ?string $apiKey;

    public function __construct(
        protected RagService $ragService,
    ) {
        $this->baseUrl = rtrim(config('services.python_ai.base_url'), '/');
        $this->apiKey = config('services.python_ai.api_key');
    }

    /**
     * Minta rekomendasi pemeliharaan terstruktur untuk satu aset.
     *
     * @return array Response MaintenanceRecommendationResponse dari AI Engine.
     */
    public function recommend(int $assetId, int $topK = 5, ?string $keluhan = null): array
    {
        $asset = Asset::with([
            'operatingHours' => fn ($q) => $q->orderByDesc('tanggal')->orderByDesc('id'),
            'maintenanceReports' => fn ($q) => $q->orderByDesc('tanggal_pemeliharaan')->orderByDesc('id'),
            'maintenanceReports.operatingHour',
        ])->findOrFail($assetId);

        // Field turunan (tidak tersimpan langsung di tabel assets)
        $totalOperatingHours = (int) ($asset->operatingHours->first()->jam_jalan ?? 0);
        $maintenanceKe = $asset->maintenanceReports->count() + 1; // pemeliharaan berikutnya

        // Retrieval RAG: sertakan keluhan jika ada agar retrieval lebih kontekstual
        $query = $keluhan
            ? "Perbaikan {$asset->nama_mesin}: {$keluhan}"
            : "Rekomendasi perawatan {$asset->nama_mesin} kategori {$asset->kategori}";
        $retrieval = $this->ragService->retrieve($query, $assetId, $topK);

        $payload = [
            'asset_id' => $asset->id,
            'asset_info' => [
                'nama_mesin' => $asset->nama_mesin,
                'kategori' => $asset->kategori,
                'area_mesin' => $asset->area_mesin,
                'maintenance_interval_hours' => (int) $asset->maintenance_interval_hours,
                'total_operating_hours' => $totalOperatingHours,
                'maintenance_ke' => $maintenanceKe,
            ],
            'keluhan_kerusakan' => $keluhan,
            'retrieved_chunks' => collect($retrieval['contexts'])->map(function ($c) {
                $metadata = $c['metadata'] ?? [];

                return [
                    'chunk_text' => $c['chunk_text'] ?? '',
                    'source_name' => $metadata['asset_name']
                        ?? $metadata['source_type']
                        ?? 'knowledge_base',
                    'relevance_score' => $c['score'] ?? null,
                    'metadata' => $metadata ?: null,
                ];
            })->values()->all(),
            'histori_maintenance' => $asset->maintenanceReports->map(fn ($r) => [
                'tanggal' => (string) $r->tanggal_pemeliharaan,
                'catatan' => (string) ($r->catatan_pemeliharaan ?? '-'),
                'jam_operasi' => $r->operatingHour?->jam_jalan !== null
                    ? (int) $r->operatingHour->jam_jalan
                    : null,
            ])->values()->all(),
        ];

        $response = Http::timeout(180)
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->post($this->baseUrl . '/api/v1/query/maintenance-recommendation', $payload);

        if (!$response->successful()) {
            throw new RuntimeException('AI Engine recommendation gagal: ' . $response->body());
        }

        return $response->json();
    }
}
