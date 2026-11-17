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
            'payroll:id,employee_id,net_pay',
            'contributionRate.contributionType:id,name',
        ]);
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