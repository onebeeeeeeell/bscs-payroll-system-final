<?php

namespace App\Services;

use App\Models\ContributionRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ContributionRateService
{
    /*
     * LABEL: Contribution rate list
     * Purpose: Nasa ContributionRate model na ang logic (eager load, search).
     * Dito, tinatawag na lang ang mga scopes.
     * withQueryString(): ini-keep ang ?search= sa pagination links.
     */
    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return ContributionRate::query()
            ->withType()
            ->search($search)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }


    public function create(array $data): ContributionRate
    {
        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => ContributionRate::create($data)
        );
    }


    public function update(ContributionRate $contributionRate, array $data): ContributionRate
    {
        return DB::transaction(function () use ($contributionRate, $data) {
            $contributionRate->update($data);

            return $contributionRate;
        });
    }


    public function delete(ContributionRate $contributionRate): void
    {
        DB::transaction(
            fn () => $contributionRate->delete()
        );
    }

}