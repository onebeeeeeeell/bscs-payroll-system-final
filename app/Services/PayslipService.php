<?php

namespace App\Services;

use App\Models\Payslip;
use App\Services\Concerns\SanitizesText;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class PayslipService
{
    use SanitizesText;

    private const SANITIZED_FIELDS = [
        'payroll_id',
        'date_generated',
    ];

    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Payslip::query()
            ->withDetails()
            ->latest('date_generated')
            ->paginate($perPage)
            ->withQueryString(); // to keep ?search= when changing pages
    }

    public function create(array $data): Payslip
    {
        $data = $this->sanitizeFields($data);

        // transaction, so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => Payslip::create($data)
        );
    }

    public function update(Payslip $payslip, array $data): Payslip
    {
        $data = $this->sanitizeFields($data);

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

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeFields(array $data): array
    {
        return $this->sanitizeStringFields($data, self::SANITIZED_FIELDS, false);
    }

    // diriiiiiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}