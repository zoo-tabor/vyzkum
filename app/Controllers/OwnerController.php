<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\SampleRepository;
use App\Services\OwnerRegistrationService;

final class OwnerController
{
    private ?SampleRepository $samples = null;
    private ?OwnerRegistrationService $registration = null;

    public function show(string $sampleId, string $token): string
    {
        $sample = $this->samples()->findForToken($sampleId, $token, 'owner');
        if (!$sample) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Vzorek nenalezen']);
        }

        return view('owner/form', [
            'title' => 'Registrace psa',
            'sample' => $sample,
            'token' => $token,
            'errors' => [],
        ]);
    }

    public function submit(string $sampleId, string $token): string
    {
        Csrf::verify();
        $sample = $this->samples()->findForToken($sampleId, $token, 'owner');
        if (!$sample) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Vzorek nenalezen']);
        }

        if ($sample['owner_submitted_at'] !== null) {
            return view('owner/done', ['title' => 'Registrace jiz byla odeslana', 'sample' => $sample]);
        }

        $data = $this->payload($sample);
        $errors = $this->validate($data, $_FILES['pedigree'] ?? null);

        if ($errors !== []) {
            return view('owner/form', [
                'title' => 'Registrace psa',
                'sample' => array_merge($sample, $data),
                'token' => $token,
                'errors' => $errors,
            ]);
        }

        $this->registration()->register($sample, $data, $_FILES['pedigree'] ?? null);
        return view('owner/done', ['title' => 'Registrace dokoncena', 'sample' => $sample]);
    }

    /** @param array<string, mixed> $sample @return array<string, mixed> */
    private function payload(array $sample): array
    {
        return [
            'chip_number' => trim((string) ($sample['chip_number_vet'] ?: input('chip_number'))),
            'dog_name' => trim((string) input('dog_name')),
            'breed' => trim((string) input('breed')),
            'sex' => trim((string) input('sex', 'unknown')),
            'birth_date' => trim((string) input('birth_date')),
            'pedigree_number' => trim((string) input('pedigree_number')),
            'registry' => trim((string) input('registry')),
            'health_status' => trim((string) input('health_status')),
            'health_note' => trim((string) input('health_note')),
            'owner_name' => trim((string) input('owner_name')),
            'owner_email' => trim((string) input('owner_email')),
            'owner_phone' => trim((string) input('owner_phone')),
            'future_contact_consent' => input('future_contact_consent') === '1',
            'results_consent' => input('results_consent') === '1',
            'newsletter_consent' => input('newsletter_consent') === '1',
            'main_consent' => input('main_consent') === '1',
        ];
    }

    /** @param array<string, mixed> $data @param array<string, mixed>|null $file @return array<int, string> */
    private function validate(array $data, ?array $file): array
    {
        $errors = [];
        if (!preg_match('/^[0-9]{15}$/', (string) $data['chip_number'])) {
            $errors[] = 'Cislo cipu musi mit 15 cislic.';
        }
        foreach (['dog_name', 'breed', 'birth_date', 'pedigree_number', 'health_status', 'owner_name'] as $field) {
            if ((string) $data[$field] === '') {
                $errors[] = 'Vyplnte vsechna povinna pole.';
                break;
            }
        }
        if (!filter_var($data['owner_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Vyplnte platny e-mail majitele.';
        }
        if (!in_array($data['sex'], ['male', 'female', 'unknown'], true)) {
            $errors[] = 'Vyberte pohlavi psa.';
        }
        if ($data['main_consent'] !== true) {
            $errors[] = 'Bez souhlasu nelze psa zaradit do studie.';
        }
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Nahrajte fotografii nebo sken prukazu puvodu.';
        }

        return array_values(array_unique($errors));
    }

    private function samples(): SampleRepository
    {
        return $this->samples ??= new SampleRepository();
    }

    private function registration(): OwnerRegistrationService
    {
        return $this->registration ??= new OwnerRegistrationService();
    }
}
