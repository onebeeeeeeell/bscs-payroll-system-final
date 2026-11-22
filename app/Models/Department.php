<?php

namespace App\Models;

use App\Models\Concerns\HasSearchScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
    use HasFactory, HasSearchScope;

    protected function searchColumn(): string
    {
        return 'department_name';
    }

    private const OPTIONS_CACHE_KEY = 'options:departments';
    private const OPTIONS_CACHE_MINUTES = 60;

    public const OPTION_COLUMNS = ['id', 'department_name'];

    /*
     * LABEL: Dropdown options
     * Purpose: id at department_name lang, alphabetical. Isang lugar na lang
     * para sa EmployeeService at AttendanceService.
     */
    public static function options(): Collection
    {
        // Cached: bihirang magbago ang listahan pero kailangan sa bawat index page.
        // May 60-minute na TTL bilang safety net kahit may bulk update na lumusot.
        return Cache::remember(
            self::OPTIONS_CACHE_KEY,
            now()->addMinutes(self::OPTIONS_CACHE_MINUTES),
            fn () => static::query()->alphabetical()->get(static::OPTION_COLUMNS),
        );
    }

    /*
     * LABEL: Cache invalidation
     * Purpose: Linisin ang cached options kapag may na-save o na-delete na department.
     * Dalawang beses nag-forget (ngayon at pagkatapos ng commit) para hindi
     * ma-cache ng ibang request ang lumang data habang bukas pa ang transaction.
     */
    protected static function booted(): void
    {
        $forget = function (): void {
            Cache::forget(self::OPTIONS_CACHE_KEY);
            DB::afterCommit(fn () => Cache::forget(self::OPTIONS_CACHE_KEY));
        };

        static::saved($forget);
        static::deleted($forget);
    }

    public function scopeAlphabetical(Builder $query): void
    {
        $query->orderBy('department_name');
    }

    /*
     * LABEL: Employees count scope
     * Purpose: Idagdag ang employees_count sa bawat department.
     * Ginagamit sa DepartmentService.
     */
    public function scopeWithEmployeesCount(Builder $query): void
    {
        $query->withCount('employees');
    }

    /*
     * LABEL: Active employees count scope
     * Purpose: Idagdag ang employees_count sa bawat department, pero active
     * (hindi naka-archive) na employee lang ang bilang. Para sa dashboard.
     */
    public function scopeWithActiveEmployeesCount(Builder $query): void
    {
        $query->withCount(['employees' => fn (Builder $employee) => $employee->active()]);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'department_id', 'id');
    }

}