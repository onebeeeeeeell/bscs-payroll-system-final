<?php

namespace App\Services;

use App\Models\ContributionRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ContributionRateService
{
    private const SANITIZED_TEXT_FIELDS = ['search'];

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $search = $this->sanitizeSearchTerm($search);

        return ContributionRate::query()
            ->with(['contributionType:id,name'])
            // only filter when something was typed in the search box (ilike = case-insensitive in PostgreSQL)
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->whereHas('contributionType', fn ($type) => $type->where('name', 'ilike', "%{$search}%"));
            }))
            ->latest()
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
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

    private function sanitizeSearchTerm(?string $search): ?string
    {
        return $search === null ? null : $this->sanitizeTextFields(['search' => $search])['search'];
    }

    private function sanitizeTextFields(array $data): array
    {
        foreach (self::SANITIZED_TEXT_FIELDS as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim(strip_tags($data[$field]));
            }
        }

        return $data;
    }

    // diriiiiiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}