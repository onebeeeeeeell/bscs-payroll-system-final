<?php

namespace App\Services;

use App\Models\ContributionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ContributionTypeService
{
    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return ContributionType::query()
            ->with(['contributionRates:id,contribution_type_id,rate'])
            // only filter when something was typed in the search box (ilike = case-insensitive in PostgreSQL)
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }

    public function create(array $data): ContributionType
    {
        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => ContributionType::create($data)
        );
    }

    public function update(ContributionType $contributionType, array $data): ContributionType
    {
        return DB::transaction(function () use ($contributionType, $data) {
            $contributionType->update($data);

            return $contributionType;
        });
    }

    public function delete(ContributionType $contributionType): void
    {
        DB::transaction(
            fn () => $contributionType->delete()
        );
    }

    // diri
}