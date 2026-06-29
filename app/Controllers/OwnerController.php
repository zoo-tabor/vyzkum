<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\OwnerRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Support\Paginator;

final class OwnerController
{
    private const PER_PAGE = 25;

    public function index(): string
    {
        $repo = new OwnerRepository();
        $search = trim((string) input('q'));

        $total = $repo->count($search);
        $pager = new Paginator($total, (int) input('page', 1), self::PER_PAGE);
        $rows = $repo->paginate($search, $pager->perPage, $pager->offset);

        return view('admin/owners/index', [
            'title' => 'Majitele',
            'owners' => $rows,
            'pager' => $pager,
            'search' => $search,
            'notice' => Session::flash('owner_notice'),
        ]);
    }

    public function show(string $id): string
    {
        $repo = new OwnerRepository();
        $owner = $repo->find((int) $id);
        if ($owner === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Majitel nenalezen']);
        }

        return view('admin/owners/show', [
            'title' => $owner['display_name'],
            'owner' => $owner,
            'emails' => $repo->emails((int) $id),
            'phones' => $repo->phones((int) $id),
            'dogs' => $repo->dogsOf((int) $id),
        ]);
    }

    public function create(): string
    {
        return view('admin/owners/form', [
            'title' => 'Novy majitel',
            'error' => Session::flash('owner_error'),
        ]);
    }

    public function store(): string
    {
        Csrf::verify();

        $displayName = trim((string) input('display_name'));
        if ($displayName === '') {
            Session::flash('owner_error', 'Vyplnte jmeno majitele.');
            redirect('/admin/owners/new');
        }

        $primaryEmail = trim((string) input('primary_email'));
        if ($primaryEmail !== '' && !filter_var($primaryEmail, FILTER_VALIDATE_EMAIL)) {
            Session::flash('owner_error', 'Primarni e-mail nema platny format.');
            redirect('/admin/owners/new');
        }

        $repo = new OwnerRepository();
        $id = $repo->create([
            'display_name' => $displayName,
            'first_name' => trim((string) input('first_name')) ?: null,
            'last_name' => trim((string) input('last_name')) ?: null,
            'address' => trim((string) input('address')) ?: null,
            'preferred_contact_method' => (string) input('preferred_contact_method', 'email'),
            'contact_consent' => (bool) input('contact_consent'),
            'note' => trim((string) input('note')) ?: null,
        ]);

        if ($primaryEmail !== '') {
            $repo->addEmail($id, $primaryEmail, true);
        }
        foreach ($this->splitList((string) input('secondary_emails')) as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $repo->addEmail($id, $email, false);
            }
        }
        foreach ($this->splitList((string) input('phones')) as $phone) {
            $repo->addPhone($id, $phone, null, false);
        }

        AuditService::log(Auth::id(), Auth::role(), 'owner_created', 'owner', (string) $id, null, ['display_name' => $displayName]);
        Session::flash('owner_notice', 'Majitel byl vytvoren.');
        redirect('/admin/owners/' . $id);
    }

    /** @return array<int, string> */
    private function splitList(string $raw): array
    {
        $parts = array_map('trim', explode(';', $raw));
        return array_values(array_filter($parts, static fn (string $s): bool => $s !== ''));
    }
}
