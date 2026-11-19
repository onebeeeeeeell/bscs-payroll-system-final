<?php

namespace App\Services;

use App\Models\Payslip;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class PayslipService
{
    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return Payslip::query()
            ->withDetails()
            ->search($search)
            ->latest('date_generated')
            ->paginate($perPage)
            ->withQueryString(); // to keep ?search= when changing pages
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

    public function delete(Payslip $payslip): void
    {
        DB::transaction(
            fn () => $payslip->delete()
        );
    }

    // diriiiiiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}