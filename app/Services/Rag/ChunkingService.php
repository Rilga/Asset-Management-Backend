<?php

namespace App\Services\Rag;

class ChunkingService
{
    public function chunk(string $text, int $maxLength = 300, int $overlap = 50): array
    {
        $text = preg_replace('/\s+/', ' ', trim($text));

        if (empty($text)) {
            return [];
        }

        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $chunk = mb_substr($text, $start, $maxLength);

            if (!empty(trim($chunk))) {
                $chunks[] = trim($chunk);
            }

            $start += ($maxLength - $overlap);

            if ($start < 0 || $start >= $length) {
                break;
            }
        }

        return $chunks;
    }
}