<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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

    /*
     * LABEL: Eager load contribution type
     * Purpose: with() para i-load na agad ang contributionType (iwas N+1 problem).
     * ':id,name' = piling columns lang para magaan ang query.
     */
    public function scopeWithType(Builder $query): void
    {
        $query->with(['contributionType:id,name']);
    }

    /*
     * LABEL: Latest rate per type scope
     * Purpose: Ang pinakabagong rate (pinakamataas na id) ng bawat contribution
     * type, para sa payroll deductions. Isang query lang, may subquery.
     */
    public function scopeLatestPerType(Builder $query): void
    {
        $query->whereIn('id', fn ($latest) => $latest
            ->from('contribution_rates')
            ->selectRaw('MAX(id)')
            ->groupBy('contribution_type_id'));
    }

    public function scopeSearch(Builder $query, ?string $search): void
    {
        if ($search) {
            $query->whereHas('contributionType', fn (Builder $type) => $type->search($search));
        }
    }

    public function contributionType(): BelongsTo
    {
        return $this->belongsTo(ContributionType::class, 'contribution_type_id', 'id');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(Deduction::class, 'contribution_rate_id', 'id');
    }

}