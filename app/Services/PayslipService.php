<?php

namespace App\Services;

use App\Models\Payslip;
use App\Services\Concerns\SanitizesText;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class PayslipService
{
    use SanitizesText;

    /**
     * @param array<string, mixed> $filters search, status
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return Payslip::query()
            ->withDetails()
            ->forFilters([...$filters, 'search' => $this->sanitizeSearch($filters['search'] ?? null)])
            ->latest('date_generated')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString(); // keep the filters when changing pages
    }

    public function create(array $data): Payslip
    {
        // transaction, so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => Payslip::create($data)
        );
    }

    public function update(Payslip $payslip, array $data): Payslip
    {
        return DB::transaction(function () use ($payslip, $data) {
            $payslip->update($data);

            return $payslip;
        });
    }

    /*
     * LABEL: Archive payslip
     * Purpose: Soft archive; ang payroll at ang payslip row ay hindi binubura.
     */
    public function archive(Payslip $payslip): void
    {
        $payslip->archive();
    }

    public function restore(Payslip $payslip): void
    {
        $payslip->restore();
    }

    // diriiiiiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}