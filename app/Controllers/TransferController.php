<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\TransferRepository;
use App\Services\OwnershipTransferService;
use App\Services\TokenService;

final class TransferController
{
    public function show(string $token): string
    {
        $request = (new TransferRepository())->findActiveByHash(TokenService::hash($token));
        if ($request === null) {
            return view('transfer/confirm', ['title' => 'Převzetí psa', 'invalid' => true, 'token' => $token, 'request' => null, '_layout' => 'public']);
        }
        return view('transfer/confirm', ['title' => 'Převzetí psa', 'invalid' => false, 'token' => $token, 'request' => $request, '_layout' => 'public']);
    }

    public function confirm(string $token): string
    {
        Csrf::verify();
        $repo = new TransferRepository();
        $request = $repo->findActiveByHash(TokenService::hash($token));
        if ($request === null) {
            return view('transfer/confirm', ['title' => 'Převzetí psa', 'invalid' => true, 'token' => $token, 'request' => null, '_layout' => 'public']);
        }

        $result = (new OwnershipTransferService())->confirm($request);
        return view('transfer/done', [
            'title' => 'Převzetí potvrzeno',
            'request' => $request,
            'inviteSent' => $result['invite_sent'],
            '_layout' => 'public',
        ]);
    }
}
