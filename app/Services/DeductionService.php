<?php

namespace App\Services;

use App\Models\Deduction;
use App\Services\Concerns\SanitizesText;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class DeductionService
{
    use SanitizesText;

    private const SANITIZED_FIELDS = [
        'payroll_id',
        'contribution_rate_id',
        'amount',
    ];

    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Deduction::query()
            ->withDetails()
            ->latest()
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }

    public function create(array $data): Deduction
    {
        $data = $this->sanitizeFields($data);

        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => Deduction::create($data)
        );
    }

    public function update(Deduction $deduction, array $data): Deduction
    {
        $data = $this->sanitizeFields($data);

        return DB::transaction(function () use ($deduction, $data) {
            $deduction->update($data);

            return $deduction;
        });
    }

    public function delete(Deduction $deduction): void
    {
        DB::transaction(
            fn () => $deduction->delete()
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeFields(array $data): array
    {
        return $this->sanitizeStringFields($data, self::SANITIZED_FIELDS, false);
    }

    // diriiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}