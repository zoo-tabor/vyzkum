<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\MessageRepository;
use App\Services\Auth;

final class MessagesController
{
    public const STATUSES = ['open', 'waiting_owner', 'resolved', 'archived'];

    public function index(): string
    {
        $repo = new MessageRepository();
        $status = (string) input('status');
        $threads = $repo->threadsList(in_array($status, self::STATUSES, true) ? $status : '');

        // Seskupeni dle majitele -> pak vlakna (obecne / konkretni pes).
        $groups = [];
        foreach ($threads as $t) {
            $ownerId = $t['owner_id'] !== null ? (int) $t['owner_id'] : 0;
            if (!isset($groups[$ownerId])) {
                $groups[$ownerId] = [
                    'owner_id' => $t['owner_id'] !== null ? (int) $t['owner_id'] : null,
                    'owner_name' => $t['owner_name'] ?: 'Neznámý majitel',
                    'threads' => [],
                ];
            }
            $groups[$ownerId]['threads'][] = $t;
        }

        return view('admin/messages/index', [
            'title' => 'Zprávy',
            'groups' => $groups,
            'status' => $status,
            'statuses' => self::STATUSES,
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
