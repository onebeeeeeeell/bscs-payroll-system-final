<?php

namespace App\Services;

use App\Models\Deduction;
use App\Services\Concerns\SanitizesText;
use Illuminate\Pagination\LengthAwarePaginator;

/*
 * Read-only: ang deductions ay ginagawa lang ng PayrollProcessingService kasabay
 * ng payroll. Walang manual create/update/delete para hindi magkaiba ang
 * total_deductions ng payroll at ang kabuuan ng deductions nito.
 */
class DeductionService
{
    use SanitizesText;

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return Deduction::query()
            ->withDetails()
            ->search($this->sanitizeSearch($search))
            ->latestFirst()
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }
}