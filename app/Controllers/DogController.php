<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\BreedRepository;
use App\Repositories\ColourRepository;
use App\Repositories\DeathCauseRepository;
use App\Repositories\DogRepository;
use App\Repositories\FormResponseRepository;
use App\Repositories\GenotypeRepository;
use App\Repositories\HealthEventRepository;
use App\Repositories\OwnerRepository;
use App\Repositories\SampleRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\BreedContext;
use App\Support\DogQuery;

final class DogController
{
    public function index(): string
    {
        $repo = new DogRepository();
        $breedId = BreedContext::current();

        // Razeni/filtrovani/strankovani resi klientska datatabulka (datatable.js),
        // proto server vraci vsechny psy vybraneho plemene v jednom seznamu.
        $built = DogQuery::filters(['q' => '', 'kennel' => '', 'status' => ''], $breedId);
        $rows = $repo->paginate($built['where'], $built['params'], 'd.name ASC, d.id ASC', 1000000, 0);

        $dogIds = array_map(static fn (array $d): int => (int) $d['id'], $rows);
        $genes = $breedId !== null ? $repo->genesForBreed($breedId) : [];

        return view('admin/dogs/index', [
            'title' => 'Psi',
            'dogs' => $rows,
            'currentBreedId' => $breedId,
            'genes' => $genes,
            'samplesByDog' => $repo->samplesForDogs($dogIds),
            'genotypesByDog' => $genes !== [] ? $repo->geneGenotypesForDogs($dogIds) : [],
            'notice' => Session::flash('dog_notice'),
        ]);
    }

    /** Naseptavac pro filtr (JSON): field=name|kennel, q=hledany text. */
    public function suggest(): never
    {
        $field = (string) input('field') === 'kennel' ? 'kennel' : 'name';
        $q = trim((string) input('q'));
        $items = $q === '' ? [] : (new DogRepository())->suggest($field, $q, BreedContext::current());

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($items, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function export(): never
    {
        $breedId = BreedContext::current();
        $filters = [
            'q' => (string) input('q'),
            'kennel' => (string) input('kennel'),
            'status' => (string) input('status'),
        ];
        $built = DogQuery::filters($filters, $breedId);
        $rows = (new DogRepository())->exportRows($built['where'], $built['params'], 'd.name ASC, d.id ASC');

        $columns = [
            'breed_slug', 'dog_name', 'kennel_name', 'sex', 'pedigree_number', 'chip_number',
            'birth_date', 'death_date', 'death_cause', 'color', 'test_group', 'health_summary',
            'owner_name', 'owner_primary_email', 'owner_secondary_emails', 'owner_phones',
            'owner_address', 'sample_received_at',
        ];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="psi_export_' . date('Ymd_His') . '.csv"');
        echo "\xEF\xBB\xBF"; // BOM pro Excel
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['breed_slug'] ?? '',
                $r['name'] ?? '',
                $r['kennel_name'] ?? '',
                $r['sex'] ?? '',
                $r['pedigree_number'] ?? '',
                $r['chip_number'] ?? '',
                $r['birth_date'] ?? '',
                $r['death_date'] ?? '',
                $r['death_cause'] ?? '',
                $r['color'] ?? '',
                $r['test_group'] ?? '',
                $r['health_summary'] ?? '',
                $r['owner_name'] ?? '',
                $r['owner_primary_email'] ?? '',
                $r['owner_secondary_emails'] ?? '',
                $r['owner_phones'] ?? '',
                $r['owner_address'] ?? '',
                $r['sample_received_at'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function show(string $id): string
    {
        $repo = new DogRepository();
        $dog = $repo->find((int) $id);
        if ($dog === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Pes nenalezen']);
        }

        return view('admin/dogs/show', [
            'title' => $dog['name'],
            'dog' => $dog,
            'currentOwner' => $repo->currentOwner((int) $id),
            'history' => $repo->ownersHistory((int) $id),
            'responses' => (new FormResponseRepository())->responsesForDog((int) $id),
            'genotypes' => (new GenotypeRepository())->byDog((int) $id),
            'healthEvents' => (new HealthEventRepository())->byDog((int) $id),
            'notice' => Session::flash('dog_notice'),
        ]);
    }

    public function create(): string
    {
        return view('admin/dogs/form', [
            'title' => 'Nový pes',
            'dog' => null,
            'breeds' => (new BreedRepository())->all(false),
            'owners' => (new OwnerRepository())->allForSelect(),
            'coloursByBreed' => (new ColourRepository())->allGrouped(),
            'defaultBreedId' => BreedContext::current(),
            'error' => Session::flash('dog_error'),
        ]);
    }

    public function store(): string
    {
        Csrf::verify();

        $data = $this->fromInput();
        if ($data['name'] === '' || (int) $data['breed_id'] <= 0) {
            Session::flash('dog_error', 'Vyplňte jméno psa a plemeno.');
            redirect('/admin/dogs/new');
        }

        $repo = new DogRepository();
        $id = $repo->create($data);

        $ownerId = (int) input('owner_id');
        if ($ownerId > 0) {
            $repo->setCurrentOwner($id, $ownerId, 'admin');
        }

        // Volitelny inline vzorek (custom cislo) - rovnou spareny s psem.
        $sampleId = trim((string) input('sample_id'));
        if ($sampleId !== '') {
            (new SampleRepository())->ensureImportedSample($sampleId, (int) $data['breed_id'], $id, trim((string) input('sample_received_at')) ?: null);
        }

        AuditService::log(Auth::id(), Auth::role(), 'dog_created', 'dog', (string) $id, null, [
            'name' => $data['name'],
            'breed_id' => $data['breed_id'],
        ]);
        Session::flash('dog_notice', 'Pes byl vytvořen.');
        redirect('/admin/dogs/' . $id);
    }

    public function edit(string $id): string
    {
        $dog = (new DogRepository())->find((int) $id);
        if ($dog === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Pes nenalezen']);
        }

        return view('admin/dogs/form', [
            'title' => 'Upravit psa',
            'dog' => $dog,
            'breeds' => (new BreedRepository())->all(false),
            'owners' => (new OwnerRepository())->allForSelect(),
            'coloursByBreed' => (new ColourRepository())->allGrouped(),
            'causeTree' => (new DeathCauseRepository())->treeForBreed((int) $dog['breed_id']),
            'defaultBreedId' => (int) $dog['breed_id'],
            'error' => Session::flash('dog_error'),
        ]);
    }

    public function update(string $id): string
    {
        Csrf::verify();

        $repo = new DogRepository();
        $dog = $repo->find((int) $id);
        if ($dog === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Pes nenalezen']);
        }

        $data = $this->fromInput();
        if ($data['name'] === '' || (int) $data['breed_id'] <= 0) {
            Session::flash('dog_error', 'Vyplňte jméno psa a plemeno.');
            redirect('/admin/dogs/' . $id . '/edit');
        }

        $repo->update((int) $id, $data);
        AuditService::log(Auth::id(), Auth::role(), 'dog_updated', 'dog', (string) $id, null, ['name' => $data['name']]);
        Session::flash('dog_notice', 'Změny byly uloženy.');
        redirect('/admin/dogs/' . $id);
    }

    public function destroy(string $id): string
    {
        Csrf::verify();
        $repo = new DogRepository();
        $dog = $repo->find((int) $id);
        if ($dog === null) {
            redirect('/admin/dogs');
        }

        $repo->delete((int) $id);
        AuditService::log(Auth::id(), Auth::role(), 'dog_deleted', 'dog', (string) $id, null, ['name' => $dog['name']]);
        Session::flash('dog_notice', 'Pes byl smazán.');
        redirect('/admin/dogs');
    }

    /** @return array<string, mixed> */
    private function fromInput(): array
    {
        $colorSelect = (string) input('color_select');
        $color = $colorSelect === '__other__' ? trim((string) input('color_other')) : $colorSelect;

        $breedId = (int) input('breed_id');
        $deathDate = (string) input('death_date');

        // Pricina umrti z kaskadoveho vyberu (jen kdyz je zadane datum umrti).
        $causeId = null;
        $causeLabel = null;
        $causeNote = null;
        if ($deathDate !== '') {
            $leaf = ((int) input('death_cause_id')) > 0
                ? (new DeathCauseRepository())->findLeaf((int) input('death_cause_id'), $breedId)
                : null;
            if ($leaf !== null) {
                $causeId = (int) $leaf['id'];
                $causeLabel = (string) $leaf['label'];
                $causeNote = ((int) $leaf['has_note'] === 1) ? (trim((string) input('death_cause_note')) ?: null) : null;
            } else {
                // Plemeno bez ciselniku pricin -> volny text.
                $causeLabel = trim((string) input('death_cause')) ?: null;
            }
        }

        return [
            'breed_id' => $breedId,
            'name' => trim((string) input('name')),
            'kennel_name' => (string) input('kennel_name'),
            'chip_number' => (string) input('chip_number'),
            'pedigree_number' => (string) input('pedigree_number'),
            'country' => strtoupper(trim((string) input('country'))) ?: null,
            'sex' => (string) input('sex', 'unknown'),
            'birth_date' => (string) input('birth_date'),
            'death_date' => $deathDate,
            'death_cause' => $causeLabel,
            'death_cause_id' => $causeId,
            'death_cause_note' => $causeNote,
            'castration_status' => trim((string) input('castration_status')) ?: null,
            'castration_date' => (string) input('castration_date'),
            'color' => $color,
            'test_group' => (string) input('test_group'),
            'health_summary' => (string) input('health_summary'),
            'status' => 'active',
        ];
    }
}
