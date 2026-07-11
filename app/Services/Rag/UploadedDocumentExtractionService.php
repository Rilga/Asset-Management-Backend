<?php

namespace App\Services\Rag;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory;
use RuntimeException;
use Smalot\PdfParser\Parser;

class UploadedDocumentExtractionService
{
    public function extract(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $text = match ($extension) {
            'pdf' => $this->extractPdf($file),
            'docx' => $this->extractDocx($file),
            'txt' => $this->extractTxt($file),
            default => throw new RuntimeException('Format file tidak didukung. Gunakan PDF, DOCX, atau TXT.'),
        };

        $text = $this->normalize($text);

        if (empty($text)) {
            throw new RuntimeException('Konten dokumen kosong atau tidak dapat diekstrak.');
        }

        return $text;
    }

    private function extractPdf(UploadedFile $file): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($file->getRealPath());

        return $pdf->getText();
    }

    private function extractDocx(UploadedFile $file): string
    {
        $phpWord = IOFactory::load($file->getRealPath());
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            $text .= $this->extractElementsText($section->getElements());
        }

        return $text;
    }

    private function extractElementsText(array $elements): string
    {
        $text = '';

        foreach ($elements as $element) {
            if (method_exists($element, 'getRows')) {
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $text .= $this->extractElementsText($cell->getElements());
                    }
                }
            } elseif (method_exists($element, 'getElements')) {
                // Container elements (e.g. TextRun) also expose getText(), which
                // just concatenates these same children - recurse only, don't also call it.
                $text .= $this->extractElementsText($element->getElements());
            } elseif (method_exists($element, 'getText')) {
                $elementText = $element->getText();
                $text .= (is_string($elementText) ? $elementText : '') . ' ';
            }
        }

        return $text;
    }

    private function extractTxt(UploadedFile $file): string
    {
        return (string) file_get_contents($file->getRealPath());
    }

    private function normalize(string $text): string
    {
        $text = str_replace('•', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
