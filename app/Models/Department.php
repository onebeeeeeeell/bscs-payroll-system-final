<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'department_name'
])]

class Department extends Model
{
    use HasFactory;

    public function scopeSearch(Builder $query, ?string $search): void
    {
        if ($search) {
            $query->where('department_name', 'ilike', "%{$search}%");
        }
    }

    public function scopeAlphabetical(Builder $query): void
    {
        $query->orderBy('department_name');
    }

    /*
     * LABEL: Employees count scope
     * Purpose: Idagdag ang employees_count sa bawat department.
     * Ginagamit sa DepartmentService at OrganizationService.
     */
    public function scopeWithEmployeesCount(Builder $query): void
    {
        $query->withCount('employees');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'department_id', 'id');
    }

}