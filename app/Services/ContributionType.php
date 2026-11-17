<?php

namespace App\Services;

use App\Models\ContributionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ContributionTypeService
{
    private const SANITIZED_TEXT_FIELDS = ['name'];

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $search = $this->sanitizeSearchTerm($search);

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
        $data = $this->sanitizeTextFields($data);

        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => ContributionType::create($data)
        );
    }

    public function update(ContributionType $contributionType, array $data): ContributionType
    {
        $data = $this->sanitizeTextFields($data);

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

    private function sanitizeSearchTerm(?string $search): ?string
    {
        return $search === null ? null : $this->sanitizeTextFields(['name' => $search])['name'];
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

    // diriiiiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}