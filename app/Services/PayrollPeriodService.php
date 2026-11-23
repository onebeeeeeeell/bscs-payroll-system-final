<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Services\Concerns\SanitizesText;
use Illuminate\Pagination\LengthAwarePaginator;

class PayrollPeriodService
{
    use SanitizesText;

    /**
     * @param array<string, mixed> $filters search, status, date
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return PayrollPeriod::query()
            ->withPayrollsCount()
            ->forFilters($this->sanitizeFilters($filters))
            ->latestFirst()
            ->paginate($perPage)
            ->withQueryString(); // keep the filters when changing pages
    }


    public function create(array $data): PayrollPeriod
    {
        return PayrollPeriod::create($data);
    }


    public function update(PayrollPeriod $payrollPeriod, array $data): PayrollPeriod
    {
        $payrollPeriod->update($data);

        return $payrollPeriod;
    }


    /*
     * LABEL: Archive period
     * Purpose: Soft archive. Walang binubura (kasama ang payrolls ng period),
     * kaya pwedeng i-archive kahit may payroll records na. Hindi na lalabas
     * sa Process payroll dropdown habang naka-archive.
     */
    public function archive(PayrollPeriod $payrollPeriod): void
    {
        $payrollPeriod->archive();
    }

    public function restore(PayrollPeriod $payrollPeriod): void
    {
        $payrollPeriod->restore();
    }


    // diriiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}