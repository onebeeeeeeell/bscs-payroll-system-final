<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        use HasFactory;

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'payroll_period_id', 'id');
    }

}