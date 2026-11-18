<?php

namespace App\Models\Concerns;

use App\Support\Like;
use Illuminate\Database\Eloquent\Builder;

trait HasSearchScope
{
    /**
     * Column na hahanapin ng ->search(). I-define sa model.
     */
    abstract protected function searchColumn(): string;

    /*
     * LABEL: Search scope
     * Purpose: Isang search scope para sa lahat ng model na may simpleng
     * "ilike" na hanap (Department, Position, ContributionType, PayrollPeriod).
     * Naka-escape ang % at _ at naka-trim ang term, kaya blank o spaces
     * lang = walang filter.
     */
    public function scopeSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search !== '') {
            $query->where($this->searchColumn(), 'ilike', Like::contains($search));
        }
    }
}