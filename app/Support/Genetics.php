<?php
declare(strict_types=1);

namespace App\Support;

final class Genetics
{
    /**
     * Z hlavicky CSV najde genotypove sloupce (konci na _genotype) a marker kod.
     *
     * @param array<int, string> $header
     * @return array<int, array{column: string, code: string}>
     */
    public static function markerColumns(array $header): array
    {
        $out = [];
        foreach ($header as $col) {
            if (preg_match('/^(.*)_genotype$/i', trim($col), $m) && trim($m[1]) !== '') {
                $out[] = ['column' => trim($col), 'code' => trim($m[1])];
            }
        }
        return $out;
    }

    /**
     * Prazdna hodnota = vysledek neni k dispozici (radek se preskoci).
     * "X" NENI prazdna - je to platny genotyp (PCR sekvenace v danem miste nevysla).
     */
    public static function isEmptyValue(string $value): bool
    {
        $v = strtoupper(trim($value));
        return $v === '' || $v === 'N/A' || $v === '-';
    }

    /**
     * Rozlozi genotyp na alely. Vraci null jen pro prazdnou hodnotu.
     * "X" (nevysla PCR) se ulozi jako genotyp "X" bez alel.
     *
     * @return array{allele_1: ?string, allele_2: ?string, genotype: string}|null
     */
    public static function splitGenotype(string $value): ?array
    {
        if (self::isEmptyValue($value)) {
            return null;
        }
        $g = strtoupper(trim($value));
        if (preg_match('/^[A-Z]{2}$/', $g)) {
            return ['allele_1' => $g[0], 'allele_2' => $g[1], 'genotype' => $g];
        }
        return ['allele_1' => null, 'allele_2' => null, 'genotype' => $g];
    }
}
