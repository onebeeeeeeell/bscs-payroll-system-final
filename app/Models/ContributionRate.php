<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
        'contribution_type_id',
        'rate'
        ])]

class ContributionRate extends Model
{
        use HasFactory;

    public function contributionType(): BelongsTo
    {
        return $this->belongsTo(ContributionType::class, 'contribution_type_id', 'id');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(Deduction::class, 'contribution_rate_id', 'id');
    }

}