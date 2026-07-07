<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\BreedRepository;
use App\Repositories\SampleRepository;
use App\Services\AuditService;
use App\Services\OwnerQrRegistrationService;

final class PublicSampleController
{
    private function samples(): SampleRepository
    {
        return new SampleRepository();
    }

    // ----- Veterinar -----

    public function vetShow(string $sampleId, string $token): string
    {
        $sample = $this->samples()->findForToken($sampleId, $token, 'vet');
        if ($sample === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Vzorek nenalezen', '_layout' => 'public']);
        }
        if ($sample['vet_submitted_at'] !== null) {
            return view('vet/done', ['title' => 'Odber jiz ulozen', 'sample' => $sample, '_layout' => 'public']);
        }
        return view('vet/form', ['title' => 'Veterinarni odber', 'sample' => $sample, 'errors' => [], '_layout' => 'public']);
    }

    public function vetSubmit(string $sampleId, string $token): string
    {
        Csrf::verify();
        $sample = $this->samples()->findForToken($sampleId, $token, 'vet');
        if ($sample === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Vzorek nenalezen', '_layout' => 'public']);
        }
        if ($sample['vet_submitted_at'] !== null) {
            return view('vet/done', ['title' => 'Odber jiz ulozen', 'sample' => $sample, '_layout' => 'public']);
        }

        $data = [
            'chip_number_vet' => trim((string) input('chip_number_vet')),
            'sample_type' => trim((string) input('sample_type')),
            'sample_type_other' => trim((string) input('sample_type_other')),
            'material_count' => trim((string) input('material_count')),
            'collection_date' => trim((string) input('collection_date')),
        ];
        $errors = [];
        if ($data['chip_number_vet'] === '') {
            $errors[] = t('Vyplňte číslo čipu.');
        }
        if ($data['sample_type'] === '') {
            $errors[] = t('Vyberte typ vzorku.');
        }
        if ($data['material_count'] === '') {
            $errors[] = t('Vyberte počet zkumavek.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['collection_date'])) {
            $errors[] = t('Vyplňte datum odběru.');
        }

        if ($errors !== []) {
            return view('vet/form', ['title' => 'Veterinarni odber', 'sample' => array_merge($sample, $data), 'errors' => $errors, '_layout' => 'public']);
        }

        $this->samples()->submitVet((int) $sample['id'], $data);
        AuditService::log(null, 'vet', 'vet_submitted', 'sample', $sampleId);
        return view('vet/done', ['title' => 'Odber ulozen', 'sample' => $sample, '_layout' => 'public']);
    }

    // ----- Majitel -----

    public function dogShow(string $sampleId, string $token): string
    {
        $sample = $this->samples()->findForToken($sampleId, $token, 'owner');
        if ($sample === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Vzorek nenalezen', '_layout' => 'public']);
        }
        if ($sample['owner_submitted_at'] !== null) {
            return view('dog/done', ['title' => 'Registrace odeslana', 'sample' => $sample, '_layout' => 'public']);
        }
        return view('dog/form', [
            'title' => 'Registrace psa',
            'sample' => $sample,
            'breeds' => empty($sample['breed_id']) ? (new BreedRepository())->all() : [],
            'errors' => [],
            '_layout' => 'public',
        ]);
    }

    public function dogSubmit(string $sampleId, string $token): string
    {
        Csrf::verify();
        $sample = $this->samples()->findForToken($sampleId, $token, 'owner');
        if ($sample === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Vzorek nenalezen', '_layout' => 'public']);
        }
        if ($sample['owner_submitted_at'] !== null) {
            return view('dog/done', ['title' => 'Registrace odeslana', 'sample' => $sample, '_layout' => 'public']);
        }

        $data = [
            'chip_number' => trim((string) input('chip_number')),
            'dog_name' => trim((string) input('dog_name')),
            'sex' => (string) input('sex', 'unknown'),
            'birth_date' => trim((string) input('birth_date')),
            'pedigree_number' => trim((string) input('pedigree_number')),
            'owner_name' => trim((string) input('owner_name')),
            'owner_email' => trim((string) input('owner_email')),
            'owner_phone' => trim((string) input('owner_phone')),
            'owner_address' => trim((string) input('owner_address')),
            'future_contact_consent' => (bool) input('future_contact_consent'),
        ];

        // Plemeno: bud z davky (sample.breed_id), nebo z formulare.
        $breeds = new BreedRepository();
        if (!empty($sample['breed_id'])) {
            $breedId = (int) $sample['breed_id'];
            $breedSlug = (string) ($sample['breed_slug'] ?? '');
        } else {
            $breedId = (int) input('breed_id');
            $breed = $breedId > 0 ? $breeds->find($breedId) : null;
            $breedSlug = $breed !== null ? (string) $breed['slug'] : '';
        }

        $errors = $this->validateDog($data, $breedId);
        $file = $_FILES['pedigree'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = t('Nahrajte sken/foto průkazu původu (rodokmen).');
        }

        if ($errors !== []) {
            return view('dog/form', [
                'title' => 'Registrace psa',
                'sample' => array_merge($sample, $data),
                'breeds' => empty($sample['breed_id']) ? $breeds->all() : [],
                'errors' => $errors,
                '_layout' => 'public',
            ]);
        }

        try {
            (new OwnerQrRegistrationService())->register($sample, $data, $file, $breedId, $breedSlug);
        } catch (\Throwable $e) {
            return view('dog/form', [
                'title' => 'Registrace psa',
                'sample' => array_merge($sample, $data),
                'breeds' => empty($sample['breed_id']) ? $breeds->all() : [],
                'errors' => [t('Registraci se nepodařilo uložit: ') . $e->getMessage()],
                '_layout' => 'public',
            ]);
        }

        AuditService::log(null, 'owner_qr', 'owner_registered', 'sample', $sampleId);
        return view('dog/done', ['title' => 'Registrace odeslana', 'sample' => $sample, '_layout' => 'public']);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validateDog(array $data, int $breedId): array
    {
        $errors = [];
        if ($breedId <= 0) {
            $errors[] = t('Vyberte plemeno.');
        }
        if ($data['chip_number'] === '') {
            $errors[] = t('Vyplňte číslo čipu.');
        }
        if ($data['dog_name'] === '') {
            $errors[] = t('Vyplňte jméno psa.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['birth_date'])) {
            $errors[] = t('Vyplňte datum narození.');
        }
        if ($data['pedigree_number'] === '') {
            $errors[] = t('Vyplňte číslo průkazu / zápisu.');
        }
        if ($data['owner_name'] === '') {
            $errors[] = t('Vyplňte jméno majitele.');
        }
        if (!filter_var($data['owner_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = t('Vyplňte platný e-mail majitele.');
        }
        if (empty(input('main_consent'))) {
            $errors[] = t('Bez souhlasu nelze psa do studie zařadit.');
        }
        return $errors;
    }
}
