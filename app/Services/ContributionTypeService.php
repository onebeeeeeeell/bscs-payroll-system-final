<?php

namespace App\Services;

use App\Models\ContributionType;
use App\Services\Concerns\SanitizesText;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ContributionTypeService
{
    use SanitizesText;

    private const SANITIZED_FIELDS = ['name'];

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return ContributionType::query()
            ->withRates()
            ->search($this->sanitizeSearch($search))
            ->latestFirst()
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }


    public function create(array $data): ContributionType
    {
        $data = $this->sanitizeFields($data);

        try {
            return ContributionType::create($data);
        } catch (UniqueConstraintViolationException) {
            // sabay na submit na lumusot sa validation: ang unique index ang humarang
            throw $this->duplicateName();
        }
    }


    public function update(ContributionType $contributionType, array $data): ContributionType
    {
        $data = $this->sanitizeFields($data);

        try {
            $contributionType->update($data);
        } catch (UniqueConstraintViolationException) {
            throw $this->duplicateName();
        }

        return $contributionType;
    }


    public function delete(ContributionType $contributionType): void
    {
        DB::transaction(function () use ($contributionType) {
            // Cascade ang FK: buburahin ang mga rate ng type, at ang mga rate
            // ay may deductions na nakakabit sa lumang payrolls. Kaya type na
            // walang rate lang ang pwedeng i-delete.
            if ($contributionType->contributionRates()->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'name' => 'This contribution type has rates and cannot be deleted.',
                ]);
            }

            $contributionType->delete();
        });
    }


    private function duplicateName(): ValidationException
    {
        return ValidationException::withMessages([
            'name' => 'A contribution type with this name already exists.',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeFields(array $data): array
    {
        return $this->sanitizeStringFields($data, self::SANITIZED_FIELDS);
    }
}