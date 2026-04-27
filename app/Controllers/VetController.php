<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\SampleRepository;

final class VetController
{
    private ?SampleRepository $samples = null;

    public function show(string $sampleId, string $token): string
    {
        $sample = $this->samples()->findForToken($sampleId, $token, 'vet');
        if (!$sample) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Vzorek nenalezen']);
        }

        return view('vet/form', [
            'title' => 'Veterinarni odber',
            'sample' => $sample,
            'token' => $token,
            'errors' => [],
        ]);
    }

    public function submit(string $sampleId, string $token): string
    {
        Csrf::verify();
        $sample = $this->samples()->findForToken($sampleId, $token, 'vet');
        if (!$sample) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Vzorek nenalezen']);
        }

        if ($sample['vet_submitted_at'] !== null) {
            return view('vet/done', ['title' => 'Odber jiz byl ulozen', 'sample' => $sample]);
        }

        $data = [
            'chip_number_vet' => trim((string) input('chip_number_vet')),
            'sample_type' => trim((string) input('sample_type')),
            'sample_type_other' => trim((string) input('sample_type_other')),
            'material_count' => trim((string) input('material_count')),
            'collection_date' => trim((string) input('collection_date')),
        ];
        $errors = $this->validate($data);

        if ($errors !== []) {
            return view('vet/form', [
                'title' => 'Veterinarni odber',
                'sample' => array_merge($sample, $data),
                'token' => $token,
                'errors' => $errors,
            ]);
        }

        $this->samples()->submitVet((int) $sample['id'], $data);
        return view('vet/done', ['title' => 'Odber ulozen', 'sample' => $sample]);
    }

    /** @param array<string, string> $data @return array<int, string> */
    private function validate(array $data): array
    {
        $errors = [];
        if (!preg_match('/^[0-9]{15}$/', $data['chip_number_vet'])) {
            $errors[] = 'Cislo cipu musi mit 15 cislic.';
        }
        if ($data['sample_type'] === '') {
            $errors[] = 'Vyberte typ vzorku.';
        }
        if ($data['material_count'] === '') {
            $errors[] = 'Vyberte pocet odebranych zkumavek.';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['collection_date'])) {
            $errors[] = 'Vyplnte datum odberu.';
        }

        return $errors;
    }

    private function samples(): SampleRepository
    {
        return $this->samples ??= new SampleRepository();
    }
}
