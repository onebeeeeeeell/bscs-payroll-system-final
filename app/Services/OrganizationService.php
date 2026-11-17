<?php

namespace App\Services;

use App\Models\ContributionRate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Position;
use Illuminate\Support\Collection;

class OrganizationService
{
    /**
     * Everything the Settings > Organization page needs.
     */
    public function settings(): array
    {
        return [
            'departments' => Department::query()
                ->withEmployeesCount()
                ->alphabetical()
                ->get(['id', 'department_name']),
            'positions' => Position::query()
                ->withEmployeesCount()
                ->alphabetical()
                ->get(['id', 'title']),
            'statuses' => $this->statuses(),
            'contributionRates' => ContributionRate::query()
                ->withType()
                ->get(['id', 'contribution_type_id', 'rate']),
        ];
    }

    private function statuses(): Collection
    {
        $counts = Employee::query()
            ->countsByStatus()
            ->pluck('total', 'status');

        return EmploymentStatus::alphabetical()
            ->get(['id', 'name'])
            ->map(fn ($status) => [
                'id' => $status->id,
                'name' => $status->name,
                'employees_count' => (int) ($counts[$status->name] ?? 0),
            ]);
    }
}