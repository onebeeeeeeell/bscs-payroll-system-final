<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class DepartmentService
{
    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return Department::query()
            ->withCount('employees')
            // only filter when something was typed in the search box (ilike = case-insensitive in PostgreSQL)
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('department_name', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }

    public function create(array $data): Department
    {
        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => Department::create($data)
        );
    }

    public function update(Department $department, array $data): Department
    {
        return DB::transaction(function () use ($department, $data) {
            $department->update($data);

            return $department;
        });
    }

    public function delete(Department $department): void
    {
        DB::transaction(
            fn () => $department->delete()
        );
    }

    // diriiiiiiiiiiiiiiiiii sugpon if may ano sa controller na logic
}