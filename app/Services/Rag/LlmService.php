<?php

namespace App\Services\Rag;

class LlmService
{
    public function generateAnswer(string $question, array $contexts): string
    {
        if (empty($contexts)) {
            return 'Maaf, saya belum menemukan konteks yang relevan dari basis pengetahuan aset.';
        }

        $contextText = collect($contexts)
            ->pluck('chunk_text')
            ->take(3)
            ->implode("\n\n");

        return "Pertanyaan: {$question}\n\n"
            . "Jawaban sementara berdasarkan konteks yang ditemukan:\n\n"
            . $contextText;
    }
}