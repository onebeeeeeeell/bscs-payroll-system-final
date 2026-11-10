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
        $term = mb_strtolower(trim($term));

        if ($term === '') {
            return collect();
        }

        // escape LIKE wildcards so a "%" typed by the user is treated as a normal character
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        return Employee::query()
            ->with(['position:id,title', 'department:id,department_name'])
            ->whereNull('archived_at')
            ->where(function ($query) use ($like) {
                // lower() on both sides so it works the same on PostgreSQL and MySQL
                $query->whereRaw('lower(first_name) like ?', [$like])
                    ->orWhereRaw('lower(last_name) like ?', [$like])
                    ->orWhereRaw("lower(concat(first_name, ' ', last_name)) like ?", [$like]);
            })
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