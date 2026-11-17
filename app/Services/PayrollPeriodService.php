<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class PayrollPeriodService
{
    private const SANITIZED_TEXT_FIELDS = ['status'];

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $search = $this->sanitizeSearchTerm($search);

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
        $data = $this->sanitizeTextFields($data);

        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => PayrollPeriod::create($data)
        );
    }

    public function update(PayrollPeriod $payrollPeriod, array $data): PayrollPeriod
    {
        $data = $this->sanitizeTextFields($data);

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

    private function sanitizeSearchTerm(?string $search): ?string
    {
        return $search === null ? null : $this->sanitizeTextFields(['status' => $search])['status'];
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

    // diriiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}