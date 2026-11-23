<?php

namespace App\Models;

use App\Models\Concerns\HasSearchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'name'
])]

class ContributionType extends Model
{
    use HasFactory, HasSearchScope;

    /*
     * LABEL: Eager load rates
     * Purpose: with() para i-load na agad ang contributionRates (iwas N+1 problem).
     * Piling columns lang ang kukunin para magaan ang query.
     */
    public function scopeWithRates(Builder $query): void
    {
        $query->with(['contributionRates:id,contribution_type_id,calculation_type,rate']);
    }

    /*
     * LABEL: Selection scope
     * Purpose: Options para sa dropdown ng rate form: alphabetical, id at name lang.
     */
    public function scopeForSelection(Builder $query): void
    {
        $query->orderBy('name')->select(['id', 'name']);
    }

    public function scopeLatestFirst(Builder $query): void
    {
        $query->latest()->latest('id');
    }

    protected function searchColumn(): string
    {
        return 'name';
    }

    public function contributionRates(): HasMany
    {
        return $this->hasMany(ContributionRate::class, 'contribution_type_id', 'id');
    }

}