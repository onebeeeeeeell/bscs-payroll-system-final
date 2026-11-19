<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'payroll_id',
    'date_generated'
])]

class Payslip extends Model
{
    use HasFactory;

    /*
     * LABEL: Eager load details
     * Purpose: I-load na agad ang payroll at ang employee nito
     * (iwas N+1 problem). Piling columns lang para magaan ang query.
     */
    public function scopeWithDetails(Builder $query): void
    {
        $query->with([
            'payroll:id,employee_id,gross_pay,total_deductions,net_pay',
            'payroll.employee:id,first_name,last_name,suffix',
        ]);
    }

    /*
     * LABEL: Search scope
     * Purpose: Hanapin ayon sa first o last name ng employee ng payroll.
     * Walang search = walang filter. Tinatawag bilang ->search($term).
     */
    public function scopeSearch(Builder $query, ?string $search): void
    {
        if ($search) {
            $query->whereHas('payroll.employee', fn (Builder $employee) => $employee->matchingFirstOrLastName($search));
        }
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id', 'id');
    }

}