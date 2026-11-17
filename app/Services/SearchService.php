<?php


namespace App\Services;


use App\Models\Employee;
use Illuminate\Support\Collection;


class SearchService
{
    /**
     * Search active (not archived) employees by first name, last name, or full name.
     *
     * @return Collection<int, array{id: int, name: string, position: string|null, department: string|null}>
     */
    public function employees(string $term, int $limit = 8): Collection
    {
        if (trim($term) === '') {
            return collect();
        }


        return Employee::query()
            ->with(['position:id,title', 'department:id,department_name'])
            ->active()
            ->matchingSearchTerm($term)
            ->orderBy('last_name')
            ->limit($limit)
            ->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => trim("{$employee->first_name} {$employee->last_name} {$employee->suffix}"),
                'position' => $employee->position?->title,
                'department' => $employee->department?->department_name,
            ]);
    }
}