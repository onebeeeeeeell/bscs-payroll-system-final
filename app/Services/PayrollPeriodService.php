<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Services\Concerns\SanitizesText;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class PayrollPeriodService
{
    use SanitizesText;

    private const SANITIZED_TEXT_FIELDS = ['status'];

    private const SANITIZED_DATE_FIELDS = [
        'start_date',
        'end_date',
        'pay_date',
    ];

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return PayrollPeriod::query()
            ->withPayrollsCount()
            ->search($search)
            ->latest('start_date')
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }


    public function create(array $data): PayrollPeriod
    {
        $data = $this->sanitizeFields($data);

        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => PayrollPeriod::create($data)
        );
    }


    public function update(PayrollPeriod $payrollPeriod, array $data): PayrollPeriod
    {
        $data = $this->sanitizeFields($data);

        return DB::transaction(function () use ($payrollPeriod, $data) {
            $payrollPeriod->update($data);

            return $payrollPeriod;
        });
    }


    public function delete(PayrollPeriod $payrollPeriod): void
    {
        DB::transaction(
            fn () => $payrollPeriod->delete()
        );
    }


    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeFields(array $data): array
    {
        $data = $this->sanitizeStringFields($data, self::SANITIZED_TEXT_FIELDS);

        return $this->sanitizeStringFields($data, self::SANITIZED_DATE_FIELDS, false);
    }


    // diriiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}