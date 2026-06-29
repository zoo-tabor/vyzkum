<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\HealthEventRepository;
use App\Services\BreedContext;

final class HealthController
{
    public function index(): string
    {
        $breedId = BreedContext::current();
        $repo = new HealthEventRepository();

        return view('admin/health/index', [
            'title' => 'Zdravi',
            'breedId' => $breedId,
            'byType' => $breedId !== null ? $repo->frequencyByType($breedId) : [],
            'diseases' => $breedId !== null ? $repo->frequencyByCode($breedId, 'disease') : [],
            'examinations' => $breedId !== null ? $repo->frequencyByCode($breedId, 'examination') : [],
            'recent' => $breedId !== null ? $repo->recentForBreed($breedId, 100) : [],
        ]);
    }
}
