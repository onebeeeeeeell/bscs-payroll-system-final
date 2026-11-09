<?php

namespace App\Services;

use App\Models\ContributionRate;
use App\Models\Department;
use App\Models\EmploymentStatus;
use App\Models\PayrollPeriod;
use App\Models\Position;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    /**
     * Everything the Settings > Organization page needs.
     */
    public function settings(): array
    {
        return [
            'departments' => Department::withCount('employees')->orderBy('department_name')->get(['id', 'department_name']),
            'positions' => Position::withCount('employees')->orderBy('title')->get(['id', 'title']),
            'statuses' => $this->statuses(),
            'payrollPeriods' => PayrollPeriod::query()
                ->orderByDesc('start_date')
                ->limit(12)
                ->get(['id', 'start_date', 'end_date', 'pay_date', 'status']),
            'contributionRates' => ContributionRate::with('contributionType:id,name')
                ->get(['id', 'contribution_type_id', 'rate']),
        ];
    }

    private function statuses()
    {
        // employees store the status as plain text, so we count by name
        $counts = DB::table('employees')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return EmploymentStatus::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($status) => [
                'id' => $status->id,
                'name' => $status->name,
                'employees_count' => (int) ($counts[$status->name] ?? 0),
            ]);
    }
}