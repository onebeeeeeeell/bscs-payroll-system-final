<?php

namespace App\Models;

use App\Support\Like;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

#[Fillable([
    'first_name',
    'last_name',
    'suffix',
    'position_id',
    'department_id',
    'date_hired',
    'daily_rate',
    'sss_no',
    'philhealth_no',
    'pagibig_no',
    'tin_no',
    'status',
    'pin',
    'archived_at',
])]

class Employee extends Model
{
    use HasFactory;

    protected $hidden = [
        'pin',
    ];

    /*
     * LABEL: Active scope
     * Purpose: archived_at IS NULL -> mga hindi pa naka-archive.
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /*
     * LABEL: Archived scope
     * Purpose: archived_at IS NOT NULL -> mga naka-archive na.
     */
    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    /*
     * LABEL: Department scope
     * Purpose: I-filter by department_id. Tinatawag bilang ->inDepartment($id).
     */
    public function scopeInDepartment(Builder $query, int|string $departmentId): void
    {
        $query->where('department_id', $departmentId);
    }

    /*
     * LABEL: Position scope
     * Purpose: I-filter by position_id. Tinatawag bilang ->inPosition($id).
     */
    public function scopeInPosition(Builder $query, int|string $positionId): void
    {
        $query->where('position_id', $positionId);
    }

    public function scopeWithStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    public function scopeHireDateBounds(Builder $query): void
    {
        $query->selectRaw('MIN(date_hired) AS first_hire_date, MAX(date_hired) AS last_hire_date');
    }

    public function scopeCountsByStatus(Builder $query): void
    {
        $query->select('status')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('status');
    }

    /*
     * LABEL: Hire date scope
     * Purpose: I-filter by year at/o month ng date_hired.
     * - Year lang: Jan 1 hanggang Dec 31 ng year na 'yon.
     * - Year + month: first day hanggang last day ng month na 'yon.
     * - Month lang: whereMonth, kaya lahat ng taon na may ganoong month.
     * - Parehong null: walang ia-apply na filter.
     * whereBetween sa date range ay kayang gumamit ng index sa date_hired,
     * hindi tulad ng YEAR(date_hired) na hindi.
     */
    public function scopeHiredIn(Builder $query, ?int $year = null, ?int $month = null): void
    {
        if ($year !== null) {
            $startDate = Carbon::create($year, $month ?? 1, 1);
            $endDate = $month !== null
                ? $startDate->copy()->endOfMonth()
                : $startDate->copy()->endOfYear();

            $query->whereBetween('date_hired', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ]);
        } elseif ($month !== null) {
            $query->whereMonth('date_hired', $month);
        }
    }

    /*
     * LABEL: Eager load position at department
     * Purpose: with() para i-load na agad (iwas N+1 problem).
     * ':id,title' = piling columns lang para magaan ang query.
     */
    public function scopeWithListRelations(Builder $query): void
    {
        $query->with(['position:id,title', 'department:id,department_name']);
    }

    public function scopeLatestFirst(Builder $query): void
    {
        $query->latest()->latest('id');
    }

    public function scopeForSelection(Builder $query): void
    {
        $query->active()
            ->orderBy('first_name')
            ->select(['id', 'first_name', 'last_name', 'suffix']);
    }

    /*
     * LABEL: Payroll scope
     * Purpose: Mga employee na isasama sa payroll run: active lang, at piling
     * columns lang (kasama ang daily_rate) para magaan ang query.
     */
    public function scopeForPayroll(Builder $query): void
    {
        $query->active()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->select(['id', 'first_name', 'last_name', 'suffix', 'daily_rate']);
    }

    public function scopeActiveForPayrollRecord(Builder $query, int $employeeId): void
    {
        $query->whereKey($employeeId)
            ->active()
            ->lockForUpdate();
    }

    /*
     * LABEL: Filters scope
     * Purpose: Lahat ng index filters sa iisang scope.
     * - status: 'archived' -> archived(), default 'active' -> active().
     * - department / position: optional, tinatawag lang kung may laman.
     * - hire_year / hire_month: i-cast sa int o null, tapos hiredIn().
     *
     * @param array{department_id?: int|null, position_id?: int|null, hire_year?: int|null, hire_month?: int|null, status?: 'active'|'archived'} $filters
     */
    public function scopeForFilters(Builder $query, array $filters): void
    {
        $hireYear = filled($filters['hire_year'] ?? null) ? (int) $filters['hire_year'] : null;
        $hireMonth = filled($filters['hire_month'] ?? null) ? (int) $filters['hire_month'] : null;

        $query
            ->when(
                ($filters['status'] ?? 'active') === 'archived',
                fn (Builder $query) => $query->archived(),
                fn (Builder $query) => $query->active(),
            )
            ->when($filters['department_id'] ?? null, fn (Builder $query, $departmentId) => $query->inDepartment($departmentId))
            ->when($filters['position_id'] ?? null, fn (Builder $query, $positionId) => $query->inPosition($positionId))
            ->hiredIn($hireYear, $hireMonth);
    }

    public function scopeMatchingFirstOrLastName(Builder $query, string $search): void
    {
        $like = Like::contains(trim($search));

        $query->where(function (Builder $query) use ($like) {
            $query->where('first_name', 'ilike', $like)
                ->orWhere('last_name', 'ilike', $like);
        });
    }

    public function scopeMatchingFullName(Builder $query, string $fullName): void
    {
        $query->whereRaw(
            "lower(trim(concat(first_name, ' ', last_name, ' ', coalesce(suffix, '')))) = ?",
            [mb_strtolower(trim($fullName))],
        );
    }

    public function scopeForTimeClock(Builder $query, string $fullName): void
    {
        $query->active()
            ->matchingFullName($fullName)
            ->lockForUpdate();
    }

    /*
     * LABEL: Navbar search scope
     * Purpose: Hanap sa first name, last name, o "first last".
     * - PostgreSQL: ilike (kayang gumamit ng pg_trgm GIN indexes). Ang
     *   (first_name || ' ' || last_name) ay kailangang kapareho ng expression
     *   sa employees_full_name_trgm_index; huwag palitan ng concat() kasi
     *   hindi na tatama sa index.
     * - Ibang DB (SQLite): like na, na case-insensitive na doon.
     * - Walang lower() sa column, kasi hindi 'yon magagamit ng trigram index.
     */
    public function scopeMatchingSearchTerm(Builder $query, string $term): void
    {
        $like = Like::contains(trim($term));
        $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query->where(function (Builder $query) use ($like, $operator) {
            $query->where('first_name', $operator, $like)
                ->orWhere('last_name', $operator, $like)
                ->orWhereRaw("(first_name || ' ' || last_name) {$operator} ?", [$like]);
        });
    }

    public function scopeForNavbarSearch(Builder $query, string $term, int $limit = 8): void
    {
        $query
            ->active()
            ->matchingSearchTerm($term)
            ->withListRelations()
            ->orderBy('last_name')
            ->limit($limit);
    }

    public function loadShowDetails(): static
    {
        return $this->loadMissing([
            'position:id,title',
            'department:id,department_name',
        ]);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id', 'id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id', 'id');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'employee_id', 'id');
    }

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'pin' => 'hashed',
        ];
    }
}