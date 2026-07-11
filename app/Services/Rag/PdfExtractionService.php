<?php

namespace App\Services\Rag;

use App\Services\Storage\SupabaseStorageService;
use RuntimeException;
use Smalot\PdfParser\Parser;

class PdfExtractionService
{
    public function __construct(
        protected SupabaseStorageService $supabaseStorageService
    ) {
    }

    public function extractText(string $filePath): string
    {
        $pdfBinary = $this->supabaseStorageService->download($filePath);

        $tempPath = storage_path('app/temp-manual-book-' . uniqid() . '.pdf');

        file_put_contents($tempPath, $pdfBinary);

        $parser = new Parser();
        $pdf = $parser->parseFile($tempPath);

        @unlink($tempPath);

        $text = trim($pdf->getText());

        $text = str_replace('•', ' ', $text);
        $text = preg_replace('/\b\d+\./', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (empty($text)) {
            throw new RuntimeException('Teks PDF kosong atau tidak dapat diekstrak.');
        }

        return $text;
    }
}