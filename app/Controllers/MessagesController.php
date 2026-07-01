<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\MessageRepository;
use App\Repositories\OwnerRepository;
use App\Services\Auth;

final class MessagesController
{
    public const STATUSES = ['open', 'waiting_owner', 'resolved', 'archived'];

    private static function isUnresolved(string $status): bool
    {
        return !in_array($status, ['resolved', 'archived'], true);
    }

    /** Prehled: seznam majitelu (jmeno tucne, kdyz ma nevyresene vlakno). */
    public function index(): string
    {
        $threads = (new MessageRepository())->threadsList('');

        $owners = [];
        foreach ($threads as $t) {
            $key = $t['owner_id'] !== null ? (int) $t['owner_id'] : 0;
            if (!isset($owners[$key])) {
                $owners[$key] = [
                    'owner_id' => $t['owner_id'] !== null ? (int) $t['owner_id'] : null,
                    'owner_name' => $t['owner_name'] ?: 'Neznámý majitel',
                    'count' => 0,
                    'unresolved' => false,
                    'last' => null,
                ];
            }
            $owners[$key]['count']++;
            if (self::isUnresolved((string) $t['status'])) {
                $owners[$key]['unresolved'] = true;
            }
            $last = (string) ($t['last_message_at'] ?? '');
            if ($owners[$key]['last'] === null || $last > $owners[$key]['last']) {
                $owners[$key]['last'] = $last;
            }
        }

        return view('admin/messages/index', [
            'title' => 'Zprávy',
            'owners' => $owners,
            'notice' => Session::flash('msg_notice'),
        ]);
    }

    /** Detail majitele: jeho vlakna (Obecna / konkretni pes), nevyresene tucne. */
    public function owner(string $id): string
    {
        $ownerId = (int) $id;
        $threads = array_values(array_filter(
            (new MessageRepository())->threadsList(''),
            static fn (array $t): bool => (int) ($t['owner_id'] ?? 0) === $ownerId
        ));

        $owner = $ownerId > 0 ? (new OwnerRepository())->find($ownerId) : null;
        $ownerName = $owner['display_name'] ?? ($ownerId > 0 ? '#' . $ownerId : 'Neznámý majitel');

        return view('admin/messages/owner', [
            'title' => 'Zprávy - ' . $ownerName,
            'ownerId' => $ownerId,
            'ownerName' => $ownerName,
            'threads' => $threads,
            'notice' => Session::flash('msg_notice'),
        ]);
    }

    public function show(string $id): string
    {
        $repo = new MessageRepository();
        $thread = $repo->thread((int) $id);
        if ($thread === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Vlákno nenalezeno']);
        }
        return view('admin/messages/show', [
            'title' => 'Vlákno',
            'thread' => $thread,
            'messages' => $repo->messages((int) $id),
            'statuses' => self::STATUSES,
            'notice' => Session::flash('msg_notice'),
        ]);
    }

    public function reply(string $id): string
    {
        Csrf::verify();
        $repo = new MessageRepository();
        $thread = $repo->thread((int) $id);
        $body = trim((string) input('body'));
        if ($thread === null || $body === '') {
            Session::flash('msg_notice', 'Zpráva nesmí být prázdná.');
            redirect('/admin/messages/' . $id);
        }
        $repo->addMessage((int) $id, Auth::id(), Auth::role(), $body, 'waiting_owner');
        redirect('/admin/messages/' . $id);
    }

    public function setStatus(string $id): string
    {
        Csrf::verify();
        $status = (string) input('status');
        if (in_array($status, self::STATUSES, true)) {
            (new MessageRepository())->setStatus((int) $id, $status);
            Session::flash('msg_notice', 'Stav vlákna změněn.');
        }
        redirect('/admin/messages/' . $id);
    }
}
