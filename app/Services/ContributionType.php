<?php

namespace App\Services;

use App\Models\ContributionType;
use App\Services\Concerns\SanitizesText;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ContributionTypeService
{
    use SanitizesText;

    private const SANITIZED_FIELDS = ['name'];

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return ContributionType::query()
            ->withRates()
            ->search($search)
            ->latest()
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }


    public function create(array $data): ContributionType
    {
        $data = $this->sanitizeFields($data);

        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => ContributionType::create($data)
        );
    }


    public function update(ContributionType $contributionType, array $data): ContributionType
    {
        $data = $this->sanitizeFields($data);

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


    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeFields(array $data): array
    {
        return $this->sanitizeStringFields($data, self::SANITIZED_FIELDS);
    }


    // diriiiiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}