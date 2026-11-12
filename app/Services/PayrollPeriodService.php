<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class PayrollPeriodService
{
    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return PayrollPeriod::query()
            ->withCount('payrolls')
            // only filter when something was typed in the search box (ilike = case-insensitive in PostgreSQL)
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('status', 'ilike', "%{$search}%");
            }))
            ->latest('start_date')
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }

    public function create(array $data): PayrollPeriod
    {
        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => PayrollPeriod::create($data)
        );
    }

    public function update(PayrollPeriod $payrollPeriod, array $data): PayrollPeriod
    {
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

    // diriiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}