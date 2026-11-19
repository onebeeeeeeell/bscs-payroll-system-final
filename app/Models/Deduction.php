<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'payroll_id',
    'contribution_rate_id',
    'amount',
])]

class Deduction extends Model
{
    use HasFactory;

    /*
     * LABEL: Eager load details
     * Purpose: I-load na agad ang payroll at contributionRate (kasama ang
     * contributionType) para iwas N+1 problem.
     */
    public function scopeWithDetails(Builder $query): void
    {
        $query->with([
            'payroll:id,employee_id,payroll_period_id,net_pay',
            'payroll.employee:id,first_name,last_name,suffix',
            'payroll.payrollPeriod:id,start_date,end_date',
            'contributionRate.contributionType:id,name',
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

    public function contributionRate(): BelongsTo
    {
        return $this->belongsTo(ContributionRate::class, 'contribution_rate_id', 'id');
    }

}