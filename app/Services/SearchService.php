<?php


namespace App\Services;

use App\Models\Employee;
use App\Services\Concerns\SanitizesText;
use Illuminate\Support\Collection;

class SearchService
{
    use SanitizesText;
    /**
     * Search active (not archived) employees by first name, last name, or full name.
     *
     * @return Collection<int, array{id: int, name: string, position: string|null, department: string|null}>
     */
    public function employees(string $term, int $limit = 8): Collection
    {
        $term = $this->sanitizeSearch($term);

        if ($term === '') {
            return collect();
        }

        $limit = max(1, min($limit, 50));

        return Employee::query()
            ->forNavbarSearch($term, $limit)
            ->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => trim("{$employee->first_name} {$employee->last_name} {$employee->suffix}"),
                'position' => $employee->position?->title,
                'department' => $employee->department?->department_name,
            ]);
    }
}