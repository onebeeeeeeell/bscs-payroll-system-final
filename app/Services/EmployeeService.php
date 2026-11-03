<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Position;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeService
{
    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return Employee::query()
            ->with(['position:id,title', 'department:id,department_name'])
            // only filter when something was typed in the search box (ilike = case-insensitive in PostgreSQL)
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->paginate($perPage)
            ->withQueryString(); // keep ?search= when changing pages
    }

    public function create(array $data): Employee
    {
        // transaction so nothing is saved if something fails in the middle
        return DB::transaction(
            fn () => Employee::create($data)//sanitizedData not data
        );
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $employee->update($data);

            return $employee;
        });
    }

    public function delete(Employee $employee): void
    {
        DB::transaction(
            fn () => $employee->delete()
        );
    }

    // diri saylo ang sa controller part na method
    public function kuhaSangPosition():array
    {
       return Position::query()->orderBy('title')->get(['id', 'title']);
    }

    public function kuhaSangDepartment():array
    {
       return Department::query()->orderBy('department_name')->get(['id', 'department_name']);
    }



}