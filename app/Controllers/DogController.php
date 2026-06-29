<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\BreedRepository;
use App\Repositories\DogRepository;
use App\Repositories\FormResponseRepository;
use App\Repositories\GenotypeRepository;
use App\Repositories\OwnerRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\BreedContext;
use App\Support\DogQuery;
use App\Support\Paginator;

final class DogController
{
    private const PER_PAGE = 25;

    public function index(): string
    {
        $repo = new DogRepository();
        $breedId = BreedContext::current();

        $filters = [
            'q' => (string) input('q'),
            'code' => (string) input('code'),
            'status' => (string) input('status'),
        ];
        $built = DogQuery::filters($filters, $breedId);
        $orderBy = DogQuery::orderBy((string) input('sort'), (string) input('dir'));

        $total = $repo->count($built['where'], $built['params']);
        $pager = new Paginator($total, (int) input('page', 1), self::PER_PAGE);
        $rows = $repo->paginate($built['where'], $built['params'], $orderBy, $pager->perPage, $pager->offset);

        return view('admin/dogs/index', [
            'title' => 'Psi',
            'dogs' => $rows,
            'pager' => $pager,
            'filters' => $filters,
            'sort' => (string) input('sort', 'name'),
            'dir' => (string) input('dir', 'asc'),
            'currentBreedId' => $breedId,
            'notice' => Session::flash('dog_notice'),
        ]);
    }

    public function export(): never
    {
        $breedId = BreedContext::current();
        $filters = [
            'q' => (string) input('q'),
            'code' => (string) input('code'),
            'status' => (string) input('status'),
        ];
        $built = DogQuery::filters($filters, $breedId);
        $orderBy = DogQuery::orderBy((string) input('sort'), (string) input('dir'));
        $rows = (new DogRepository())->exportRows($built['where'], $built['params'], $orderBy);

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
            'notice' => Session::flash('dog_notice'),
        ]);
    }

    public function create(): string
    {
        return view('admin/dogs/form', [
            'title' => 'Novy pes',
            'dog' => null,
            'breeds' => (new BreedRepository())->all(false),
            'owners' => (new OwnerRepository())->allForSelect(),
            'defaultBreedId' => BreedContext::current(),
            'error' => Session::flash('dog_error'),
        ]);
    }

    public function store(): string
    {
        Csrf::verify();

        $data = $this->fromInput();
        if ($data['name'] === '' || (int) $data['breed_id'] <= 0) {
            Session::flash('dog_error', 'Vyplnte jmeno psa a plemeno.');
            redirect('/admin/dogs/new');
        }

        $repo = new DogRepository();
        $id = $repo->create($data);

        $ownerId = (int) input('owner_id');
        if ($ownerId > 0) {
            $repo->setCurrentOwner($id, $ownerId, 'admin');
        }

        AuditService::log(Auth::id(), Auth::role(), 'dog_created', 'dog', (string) $id, null, [
            'name' => $data['name'],
            'breed_id' => $data['breed_id'],
        ]);
        Session::flash('dog_notice', 'Pes byl vytvoren.');
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
            Session::flash('dog_error', 'Vyplnte jmeno psa a plemeno.');
            redirect('/admin/dogs/' . $id . '/edit');
        }

        $repo->update((int) $id, $data);
        AuditService::log(Auth::id(), Auth::role(), 'dog_updated', 'dog', (string) $id, null, ['name' => $data['name']]);
        Session::flash('dog_notice', 'Zmeny byly ulozeny.');
        redirect('/admin/dogs/' . $id);
    }

    /** @return array<string, mixed> */
    private function fromInput(): array
    {
        return [
            'breed_id' => (int) input('breed_id'),
            'name' => trim((string) input('name')),
            'kennel_name' => (string) input('kennel_name'),
            'chip_number' => (string) input('chip_number'),
            'pedigree_number' => (string) input('pedigree_number'),
            'sex' => (string) input('sex', 'unknown'),
            'birth_date' => (string) input('birth_date'),
            'death_date' => (string) input('death_date'),
            'death_cause' => (string) input('death_cause'),
            'color' => (string) input('color'),
            'test_group' => (string) input('test_group'),
            'health_summary' => (string) input('health_summary'),
            'status' => 'active',
        ];
    }
}
