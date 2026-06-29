<?php
declare(strict_types=1);

namespace App\Support;

final class Csv
{
    /** Guess the delimiter from a header line (comma / semicolon / tab). */
    public static function detectDelimiter(string $line): string
    {
        $counts = [
            ',' => substr_count($line, ','),
            ';' => substr_count($line, ';'),
            "\t" => substr_count($line, "\t"),
        ];
        arsort($counts);
        $top = array_key_first($counts);
        return $counts[$top] > 0 ? (string) $top : ',';
    }

    /**
     * Parse CSV content into header + associative rows (keyed by header name).
     * Handles BOM, mixed newlines, quoted fields and ;/,/tab delimiters.
     *
     * @return array{header: array<int, string>, rows: array<int, array<string, string>>}
     */
    public static function parse(string $content): array
    {
        $content = (string) preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (trim($content) === '') {
            return ['header' => [], 'rows' => []];
        }

        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiter = self::detectDelimiter($firstLine);

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $content);
        rewind($fh);

        $header = null;
        $rows = [];
        while (($cells = fgetcsv($fh, 0, $delimiter, '"', '\\')) !== false) {
            if ($cells === [null] || $cells === false) {
                continue;
            }
            if ($header === null) {
                $header = array_map(static fn ($h): string => trim((string) $h), $cells);
                continue;
            }
            // skip fully empty lines
            if (count($cells) === 1 && trim((string) ($cells[0] ?? '')) === '') {
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = isset($cells[$i]) ? trim((string) $cells[$i]) : '';
            }
            $rows[] = $row;
        }
        fclose($fh);

        return ['header' => $header ?? [], 'rows' => $rows];
    }
}
