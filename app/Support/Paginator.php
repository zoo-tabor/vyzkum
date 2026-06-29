<?php
declare(strict_types=1);

namespace App\Support;

final class Paginator
{
    public int $perPage;
    public int $total;
    public int $totalPages;
    public int $page;
    public int $offset;

    public function __construct(int $total, int $page, int $perPage = 25)
    {
        $this->perPage = max(1, $perPage);
        $this->total = max(0, $total);
        $this->totalPages = max(1, (int) ceil($this->total / $this->perPage));
        $this->page = min(max(1, $page), $this->totalPages);
        $this->offset = ($this->page - 1) * $this->perPage;
    }

    public function hasPrev(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->totalPages;
    }

    public function from(): int
    {
        return $this->total === 0 ? 0 : $this->offset + 1;
    }

    public function to(): int
    {
        return min($this->offset + $this->perPage, $this->total);
    }
}
