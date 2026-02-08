<?php

namespace App\Services;

use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;

class ResumeTextExtractor
{
    public function extract(string $content, string $filename = ''): string
    {
        if ($this->isPdf($filename)) {
            return $this->extractFromPdf($content);
        }

        if ($this->isHtml($filename, $content)) {
            return strip_tags($content);
        }

        return $content;
    }

    private function isPdf(string $filename): bool
    {
        return Str::lower(pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf';
    }

    private function isHtml(string $filename, string $content): bool
    {
        return Str::contains(Str::lower($filename), '.html')
            || Str::contains(Str::lower($filename), '.htm')
            || Str::startsWith(trim($content), '<');
    }

    private function extractFromPdf(string $binaryContent): string
    {
        $parser = new PdfParser;
        $pdf = $parser->parseContent($binaryContent);

        return $pdf->getText();
    }
}
