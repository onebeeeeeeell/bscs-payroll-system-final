<?php

namespace App\Models;

use App\Models\Concerns\HasSearchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'start_date',
    'end_date',
    'pay_date',
    'status'
])]

class PayrollPeriod extends Model
{
    use HasFactory, HasSearchScope;

    /*
     * LABEL: Payrolls count scope
     * Purpose: Idagdag ang payrolls_count sa bawat payroll period.
     */
    public function scopeWithPayrollsCount(Builder $query): void
    {
        $query->withCount('payrolls');
    }

    protected function searchColumn(): string
    {
        return 'status';
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'payroll_period_id', 'id');
    }

}