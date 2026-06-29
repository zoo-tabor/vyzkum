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
        return view('admin/messages/index', [
            'title' => 'Zpravy',
            'threads' => $repo->threadsList(in_array($status, self::STATUSES, true) ? $status : ''),
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
            return view('errors/404', ['title' => 'Vlakno nenalezeno']);
        }
        return view('admin/messages/show', [
            'title' => 'Vlakno',
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
            Session::flash('msg_notice', 'Zprava nesmi byt prazdna.');
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
            Session::flash('msg_notice', 'Stav vlakna zmenen.');
        }
        redirect('/admin/messages/' . $id);
    }
}
