<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Services\Concerns\SanitizesText;
use App\Support\YearBounds;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class PayrollPeriodService
{
    use SanitizesText;

    /**
     * @param array<string, mixed> $filters search, status, date
     */
    public function indexData(array $filters): array
    {
        return [
            'payrollPeriods' => $this->paginate($filters),
            'filters' => $filters,
            // closure: only computed when the page asks for it (not on filter reloads)
            'dateBounds' => fn () => $this->dateBounds(),
        ];
    }


    /**
     * @return array{payrollPeriod: PayrollPeriod, locked: bool}
     */
    public function editData(PayrollPeriod $payrollPeriod): array
    {
        return [
            'payrollPeriod' => $payrollPeriod,
            'locked' => $payrollPeriod->isLocked(),
        ];
    }


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


    /*
     * LABEL: Create period
     * Purpose: Arr::only para dates lang ang pumasok; ang status ay laging
     * Pending (hindi galing sa request).
     */
    public function create(array $data): PayrollPeriod
    {
        return PayrollPeriod::create([
            ...Arr::only($data, ['start_date', 'end_date', 'pay_date']),
            'status' => PayrollPeriod::INITIAL_STATUS,
        ]);
    }


    public function update(PayrollPeriod $payrollPeriod, array $data): PayrollPeriod
    {
        $payrollPeriod->update(Arr::only($data, ['start_date', 'end_date', 'pay_date', 'status']));

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


    /*
     * Year range of the date filter: current year, plus any year that has a period.
     *
     * @return array{min: string, max: string}
     */
    private function dateBounds(): array
    {
        $dates = PayrollPeriod::query()->dateBounds()->first();

        return YearBounds::covering([$dates->first_date, $dates->last_date]);
    }
}