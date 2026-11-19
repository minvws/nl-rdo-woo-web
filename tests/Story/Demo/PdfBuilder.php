<?php

declare(strict_types=1);

namespace Shared\Tests\Story\Demo;

use function array_slice;
use function count;
use function explode;
use function iconv;
use function implode;
use function ksort;
use function sprintf;
use function str_replace;
use function strlen;
use function wordwrap;

/**
 * Minimal dependency free PDF writer: text only, A4, Helvetica, one or more pages.
 */
final class PdfBuilder
{
    private const int LINES_PER_PAGE = 46;
    private const int LINE_WIDTH = 88;

    /**
     * @param list<string> $paragraphs
     */
    public function build(string $title, array $paragraphs, int $pages): string
    {
        $lines = [];
        foreach ($paragraphs as $paragraph) {
            foreach (explode("\n", wordwrap($paragraph, self::LINE_WIDTH, "\n", true)) as $line) {
                $lines[] = $line;
            }
            $lines[] = '';
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        ];

        $kids = [];
        for ($page = 1; $page <= $pages; $page++) {
            $pageObject = 2 + $page * 2;
            $contentObject = $pageObject + 1;
            $kids[] = sprintf('%d 0 R', $pageObject);

            $pageLines = array_slice($lines, ($page - 1) * self::LINES_PER_PAGE, self::LINES_PER_PAGE);
            $stream = $this->pageStream($title, $page, $pages, $pageLines);

            $objects[$pageObject] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents %d 0 R >>',
                $contentObject,
            );
            $objects[$contentObject] = sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($stream), $stream);
        }

        $objects[2] = sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', implode(' ', $kids), $pages);
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= sprintf("%d 0 obj\n%s\nendobj\n", $number, $body);
        }

        $xrefOffset = strlen($pdf);
        $pdf .= sprintf("xref\n0 %d\n0000000000 65535 f \n", count($objects) + 1);
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf . sprintf("trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF\n", count($objects) + 1, $xrefOffset);
    }

    /**
     * @param list<string> $pageLines
     */
    private function pageStream(string $title, int $page, int $pages, array $pageLines): string
    {
        $stream = sprintf("BT /F1 14 Tf 50 800 Td (%s) Tj ET\n", $this->escape($title));
        $stream .= sprintf("BT /F1 9 Tf 50 30 Td (%s) Tj ET\n", $this->escape(sprintf('Pagina %d van %d', $page, $pages)));

        $y = 770;
        foreach ($pageLines as $line) {
            if ($line !== '') {
                $stream .= sprintf("BT /F1 10 Tf 50 %d Td (%s) Tj ET\n", $y, $this->escape($line));
            }
            $y -= 16;
        }

        return $stream;
    }

    private function escape(string $text): string
    {
        $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: '';

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
