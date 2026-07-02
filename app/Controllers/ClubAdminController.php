<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\BreedRepository;
use App\Repositories\UserRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\InviteService;

final class ClubAdminController
{
    public function index(): string
    {
        $users = new UserRepository();
        $clubs = $users->listByRole('club_viewer');
        foreach ($clubs as &$club) {
            $club['breed_ids'] = $users->breedIdsFor((int) $club['id']);
        }
        unset($club);

        return view('admin/clubs/index', [
            'title' => 'Kluby',
            'clubs' => $clubs,
            'breeds' => (new BreedRepository())->all(false),
            'notice' => Session::flash('club_notice'),
            'error' => Session::flash('club_error'),
        ]);
    }

    public function create(): string
    {
        Csrf::verify();
        $email = strtolower(trim((string) input('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('club_error', 'Zadejte platný e-mail.');
            redirect('/admin/clubs');
        }

        $users = new UserRepository();
        $userId = $users->ensureUser($email, 'club_viewer');
        $users->setBreedAccess($userId, array_map('intval', (array) ($_POST['breeds'] ?? [])));

        $result = (new InviteService())->sendUserInvite($userId, $email, Auth::id());
        AuditService::log(Auth::id(), Auth::role(), 'club_created', 'user', (string) $userId, null, ['email' => $email]);

        Session::flash('club_notice', 'Klubový účet připraven. ' . $result['message']);
        redirect('/admin/clubs');
    }

    public function updateAccess(string $id): string
    {
        Csrf::verify();
        (new UserRepository())->setBreedAccess((int) $id, array_map('intval', (array) ($_POST['breeds'] ?? [])));
        AuditService::log(Auth::id(), Auth::role(), 'club_access_updated', 'user', $id);
        Session::flash('club_notice', 'Přístup k plemenům aktualizován.');
        redirect('/admin/clubs');
    }

    public function destroy(string $id): string
    {
        Csrf::verify();
        $users = new UserRepository();
        $user = $users->findById((int) $id);
        if ($user === null || ($user['role'] ?? '') !== 'club_viewer') {
            Session::flash('club_error', 'Klubový účet nenalezen.');
            redirect('/admin/clubs');
        }

        $users->delete((int) $id);
        AuditService::log(Auth::id(), Auth::role(), 'club_deleted', 'user', $id, null, ['email' => $user['email']]);
        Session::flash('club_notice', 'Klubový účet byl smazán.');
        redirect('/admin/clubs');
    }
}
