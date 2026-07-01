<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\InviteRepository;
use App\Repositories\OwnerRepository;
use App\Repositories\UserRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\InviteService;
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
            'title' => 'Majitelé',
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

        $account = ['has_user' => false, 'has_password' => false, 'has_invite' => false, 'invite_expired' => false];
        if (!empty($owner['user_id'])) {
            $account['has_user'] = true;
            $user = (new UserRepository())->findById((int) $owner['user_id']);
            $account['has_password'] = $user !== null && !empty($user['password_hash']);
            $invite = (new InviteRepository())->latestForUser((int) $owner['user_id']);
            if ($invite !== null) {
                $account['has_invite'] = true;
                $account['invite_expired'] = $invite['used_at'] === null
                    && strtotime((string) $invite['expires_at']) < time();
            }
        }

        return view('admin/owners/show', [
            'title' => $owner['display_name'],
            'owner' => $owner,
            'emails' => $repo->emails((int) $id),
            'phones' => $repo->phones((int) $id),
            'dogs' => $repo->dogsOf((int) $id),
            'primaryEmail' => $repo->primaryEmail((int) $id),
            'account' => $account,
            'notice' => Session::flash('owner_notice'),
            'error' => Session::flash('owner_error'),
        ]);
    }

    public function sendPassword(string $id): string
    {
        Csrf::verify();

        $result = (new InviteService())->sendPasswordInvite((int) $id, Auth::id());
        AuditService::log(Auth::id(), Auth::role(), 'owner_password_invite', 'owner', $id, null, ['ok' => $result['ok']]);

        Session::flash($result['ok'] ? 'owner_notice' : 'owner_error', $result['message']);
        redirect('/admin/owners/' . $id);
    }

    public function create(): string
    {
        return view('admin/owners/form', [
            'title' => 'Nový majitel',
            'owner' => null,
            'primaryEmail' => '',
            'secondaryEmails' => [],
            'phones' => [],
            'error' => Session::flash('owner_error'),
        ]);
    }

    public function edit(string $id): string
    {
        $repo = new OwnerRepository();
        $owner = $repo->find((int) $id);
        if ($owner === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Majitel nenalezen']);
        }
        return view('admin/owners/form', [
            'title' => 'Upravit majitele',
            'owner' => $owner,
            'primaryEmail' => $repo->primaryEmail((int) $id) ?? '',
            'secondaryEmails' => array_values(array_filter($repo->emails((int) $id), static fn ($e) => (int) $e['is_primary'] === 0)),
            'phones' => $repo->phones((int) $id),
            'error' => Session::flash('owner_error'),
        ]);
    }

    public function update(string $id): string
    {
        Csrf::verify();
        $repo = new OwnerRepository();
        $owner = $repo->find((int) $id);
        if ($owner === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Majitel nenalezen']);
        }

        $displayName = trim((string) input('display_name'));
        if ($displayName === '') {
            Session::flash('owner_error', 'Vyplňte jméno majitele.');
            redirect('/admin/owners/' . $id . '/edit');
        }
        $primaryEmail = trim((string) input('primary_email'));
        if ($primaryEmail !== '' && !filter_var($primaryEmail, FILTER_VALIDATE_EMAIL)) {
            Session::flash('owner_error', 'Primární e-mail nemá platný formát.');
            redirect('/admin/owners/' . $id . '/edit');
        }

        $repo->update((int) $id, [
            'display_name' => $displayName,
            'first_name' => trim((string) input('first_name')) ?: null,
            'last_name' => trim((string) input('last_name')) ?: null,
            'address' => trim((string) input('address')) ?: null,
            'preferred_contact_method' => (string) input('preferred_contact_method', 'email'),
            'contact_consent' => (bool) input('contact_consent'),
            'note' => trim((string) input('note')) ?: null,
        ]);
        $repo->setPrimaryEmail((int) $id, $primaryEmail);
        $repo->replaceSecondaryEmails((int) $id, $this->splitList((string) input('secondary_emails')));
        $repo->replacePhones((int) $id, $this->splitList((string) input('phones')));

        AuditService::log(Auth::id(), Auth::role(), 'owner_updated', 'owner', $id, null, ['display_name' => $displayName]);
        Session::flash('owner_notice', 'Změny majitele byly uloženy.');
        redirect('/admin/owners/' . $id);
    }

    public function store(): string
    {
        Csrf::verify();

        $displayName = trim((string) input('display_name'));
        if ($displayName === '') {
            Session::flash('owner_error', 'Vyplňte jméno majitele.');
            redirect('/admin/owners/new');
        }

        $primaryEmail = trim((string) input('primary_email'));
        if ($primaryEmail !== '' && !filter_var($primaryEmail, FILTER_VALIDATE_EMAIL)) {
            Session::flash('owner_error', 'Primární e-mail nemá platný formát.');
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
        Session::flash('owner_notice', 'Majitel byl vytvořen.');
        redirect('/admin/owners/' . $id);
    }

    /** @return array<int, string> */
    private function splitList(string $raw): array
    {
        $parts = array_map('trim', explode(';', $raw));
        return array_values(array_filter($parts, static fn (string $s): bool => $s !== ''));
    }
}
