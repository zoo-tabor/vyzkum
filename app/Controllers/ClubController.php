<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\BreedRepository;
use App\Repositories\StatsRepository;
use App\Services\Auth;
use App\Support\Paginator;

final class ClubController
{
    public function index(): string
    {
        [$breeds, $selected] = $this->context();
        $stats = new StatsRepository();

        return view('club/dashboard', [
            'title' => 'Prehled',
            'breeds' => $breeds,
            'selected' => $selected,
            'counts' => $selected > 0 ? $stats->dogCounts($selected) : ['total' => 0, 'alive' => 0, 'dead' => 0],
            'avgAge' => $selected > 0 ? $stats->avgAgeYears($selected) : null,
            'buckets' => $selected > 0 ? $stats->ageBuckets($selected) : ['b0' => 0, 'b1' => 0, 'b2' => 0, 'b3' => 0],
            'deathCauses' => $selected > 0 ? $stats->deathCauses($selected) : [],
            'genetics' => $selected > 0 ? $stats->geneticDistribution($selected) : [],
        ]);
    }

    public function dogs(): string
    {
        [$breeds, $selected] = $this->context();
        $stats = new StatsRepository();
        $total = $selected > 0 ? $stats->dogsCount($selected) : 0;
        $pager = new Paginator($total, (int) input('page', 1), 50);

        return view('club/dogs', [
            'title' => 'Psi',
            'breeds' => $breeds,
            'selected' => $selected,
            'dogs' => $selected > 0 ? $stats->dogsForClub($selected, $pager->perPage, $pager->offset) : [],
            'pager' => $pager,
        ]);
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function context(): array
    {
        $user = Auth::user();
        $breeds = (new BreedRepository())->accessibleFor((int) $user['id'], (string) $user['role']);
        $ids = array_map(static fn (array $b): int => (int) $b['id'], $breeds);

        $selected = (int) input('breed');
        if (!in_array($selected, $ids, true)) {
            $selected = $ids[0] ?? 0;
        }
        return [$breeds, $selected];
    }
}
