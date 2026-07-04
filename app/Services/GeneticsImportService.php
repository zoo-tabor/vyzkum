<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\GeneRepository;
use App\Repositories\GenotypeRepository;
use App\Repositories\SampleRepository;
use App\Support\Genetics;

/**
 * Import PCR genotypu z siroskeho CSV (sample_id, ..., <MARKER>_genotype, ...).
 * Paruje podle sample_id -> samples.dog_id. Markery se zakladaji automaticky
 * podle hlavicky, kdyz jeste neexistuji.
 */
final class GeneticsImportService
{
    public function __construct(
        private GeneRepository $genes = new GeneRepository(),
        private GenotypeRepository $genotypes = new GenotypeRepository(),
        private SampleRepository $samples = new SampleRepository(),
    ) {
    }

    /**
     * @param array{header: array<int, string>, rows: array<int, array<string, string>>} $parsed
     * @return array{rows: array<int, array<string, mixed>>, summary: array<string, int>, markerColumns: array<int, array{column:string, code:string}>}
     */
    public function preview(array $parsed): array
    {
        $markerCols = Genetics::markerColumns($parsed['header']);
        $rows = [];
        $valid = 0;
        $invalid = 0;
        $line = 1;

        foreach ($parsed['rows'] as $r) {
            $line++;
            $sid = trim((string) ($r['sample_id'] ?? ''));
            $dog = $sid !== '' ? $this->samples->dogForSampleId($sid) : null;
            $values = 0;
            foreach ($markerCols as $mc) {
                if (!Genetics::isEmptyValue((string) ($r[$mc['column']] ?? ''))) {
                    $values++;
                }
            }
            $rows[] = [
                'line' => $line,
                'sample_id' => $sid,
                'dog_id' => $dog['dog_id'] ?? null,
                'found' => $dog !== null,
                'values' => $values,
            ];
            $dog !== null ? $valid++ : $invalid++;
        }

        return [
            'rows' => $rows,
            'summary' => [
                'total' => count($parsed['rows']),
                'valid' => $valid,
                'invalid' => $invalid,
                'markers' => count($markerCols),
            ],
            'markerColumns' => $markerCols,
        ];
    }

    /**
     * @param array{header: array<int, string>, rows: array<int, array<string, string>>} $parsed
     * @return array{tests: int, genotypes: int, skipped: int}
     */
    public function commit(array $parsed, ?int $userId): array
    {
        $markerCols = Genetics::markerColumns($parsed['header']);
        $markerIdByColumn = [];
        foreach ($markerCols as $mc) {
            $markerIdByColumn[$mc['column']] = $this->genes->ensureMarker($mc['code']);
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $tests = 0;
            $genos = 0;
            $skipped = 0;

            foreach ($parsed['rows'] as $r) {
                $sid = trim((string) ($r['sample_id'] ?? ''));
                $dog = $sid !== '' ? $this->samples->dogForSampleId($sid) : null;
                if ($dog === null) {
                    $skipped++;
                    continue;
                }

                $testId = $this->genotypes->createTest(
                    $dog['dog_id'],
                    trim((string) ($r['lab_name'] ?? '')) ?: null,
                    trim((string) ($r['tested_at'] ?? '')) ?: null,
                    'csv',
                    null,
                    trim((string) ($r['expected_phenotype'] ?? '')) ?: null
                );
                $tests++;

                foreach ($markerCols as $mc) {
                    $split = Genetics::splitGenotype((string) ($r[$mc['column']] ?? ''));
                    if ($split === null) {
                        continue;
                    }
                    $this->genotypes->upsertGenotype(
                        $dog['dog_id'],
                        $dog['breed_id'],
                        $markerIdByColumn[$mc['column']],
                        $split['allele_1'],
                        $split['allele_2'],
                        $split['genotype'],
                        $testId,
                        'imported',
                        null,
                        \App\Support\GenotypeSource::DEFAULT
                    );
                    $genos++;
                }

                // Po nahrani genetiky se vzorky psa oznaci jako analysis_done.
                $this->samples->markAnalysisDoneForDog($dog['dog_id']);
            }

            $pdo->commit();
            return ['tests' => $tests, 'genotypes' => $genos, 'skipped' => $skipped];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
