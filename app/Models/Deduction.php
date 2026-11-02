<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
    
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll:: class, 'payroll_id', 'id');
    }

    public function contributionRate(): BelongsTo
    {
        return $this->belongsTo(ContributionRate:: class, 'contribution_rate_id', 'id');
    }

}
