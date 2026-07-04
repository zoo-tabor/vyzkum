<?php
declare(strict_types=1);

namespace App\Support;

final class Age
{
    /**
     * Vek v celych letech mezi narozenim a referencnim datem.
     * reference = null -> pouzije se dnesek. Vraci null, kdyz chybi narozeni
     * nebo je reference pred narozenim.
     */
    public static function years(?string $birth, ?string $reference): ?int
    {
        if ($birth === null || trim($birth) === '') {
            return null;
        }
        $b = date_create(substr($birth, 0, 10));
        if ($b === false) {
            return null;
        }
        $r = ($reference !== null && trim($reference) !== '') ? date_create(substr($reference, 0, 10)) : date_create('today');
        if ($r === false) {
            $r = date_create('today');
        }
        if ($r < $b) {
            return null;
        }
        return (int) $b->diff($r)->y;
    }

    /**
     * Vek v letech na dve desetinna mista (xx.xx). Pocita se z poctu dnu
     * deleneho 365.25. Stejna pravidla pro null jako years().
     */
    public static function yearsFloat(?string $birth, ?string $reference): ?float
    {
        if ($birth === null || trim($birth) === '') {
            return null;
        }
        $b = date_create(substr($birth, 0, 10));
        if ($b === false) {
            return null;
        }
        $r = ($reference !== null && trim($reference) !== '') ? date_create(substr($reference, 0, 10)) : date_create('today');
        if ($r === false) {
            $r = date_create('today');
        }
        if ($r < $b) {
            return null;
        }
        return round((int) $b->diff($r)->days / 365.25, 2);
    }

    /**
     * Referencni datum pro vypocet veku dle priority:
     * datum umrti -> potvrzeni "zije" -> datum prijeti nejnovejsiho vzorku.
     */
    public static function referenceDate(?string $deathDate, ?string $aliveConfirmedAt, ?string $newestSampleReceivedAt): ?string
    {
        foreach ([$deathDate, $aliveConfirmedAt, $newestSampleReceivedAt] as $candidate) {
            if ($candidate !== null && trim($candidate) !== '') {
                return substr($candidate, 0, 10);
            }
        }
        return null;
    }
}
