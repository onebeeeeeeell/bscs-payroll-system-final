<?php

namespace App\Models;

use App\Models\Concerns\HasSearchScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'title'
])]

class Position extends Model
{
    use HasFactory, HasSearchScope;

    protected function searchColumn(): string
    {
        return 'title';
    }

    public const OPTION_COLUMNS = ['id', 'title'];

    /*
     * LABEL: Dropdown options
     * Purpose: id at title lang, alphabetical. Isang lugar na lang
     * para sa EmployeeService at AttendanceService.
     */
    public static function options(): Collection
    {
        return static::query()->alphabetical()->get(static::OPTION_COLUMNS);
    }

    public function scopeAlphabetical(Builder $query): void
    {
        $query->orderBy('title');
    }

    /*
     * LABEL: Employees count scope
     * Purpose: Idagdag ang employees_count sa bawat position.
     * Ginagamit sa PositionService at OrganizationService.
     */
    public function scopeWithEmployeesCount(Builder $query): void
    {
        $query->withCount('employees');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'position_id', 'id');
    }

}