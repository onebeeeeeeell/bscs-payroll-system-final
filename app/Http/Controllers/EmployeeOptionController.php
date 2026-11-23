<?php

namespace App\Http\Controllers;

use App\Http\Requests\Department\DepartmentRequest;
use App\Http\Requests\EmploymentStatus\EmploymentStatusRequest;
use App\Http\Requests\Position\PositionRequest;
use App\Models\Department;
use App\Models\EmploymentStatus;
use App\Models\Position;
use App\Services\DepartmentService;
use App\Services\EmploymentStatusService;
use App\Services\PositionService;
use Illuminate\Http\JsonResponse;

class EmployeeOptionController extends Controller
{
    public function __construct(
        private DepartmentService $departmentService,
        private PositionService $positionService,
        private EmploymentStatusService $employmentStatusService,
    ) {}

    public function storeDepartment(DepartmentRequest $request): JsonResponse
    {
        $department = $this->departmentService->create($request->validated());

        return response()->json(['id' => $department->id, 'label' => $department->department_name], 201);
    }

    public function storePosition(PositionRequest $request): JsonResponse
    {
        $position = $this->positionService->create($request->validated());

        return response()->json(['id' => $position->id, 'label' => $position->title], 201);
    }

    public function storeStatus(EmploymentStatusRequest $request): JsonResponse
    {
        $status = $this->employmentStatusService->create($request->validated());

        return response()->json(['id' => $status->id, 'label' => $status->name], 201);
    }

    public function updateDepartment(DepartmentRequest $request, Department $department): JsonResponse
    {
        $this->departmentService->update($department, $request->validated());

        return response()->json(['id' => $department->id, 'label' => $department->department_name]);
    }

    public function updatePosition(PositionRequest $request, Position $position): JsonResponse
    {
        $this->positionService->update($position, $request->validated());

        return response()->json(['id' => $position->id, 'label' => $position->title]);
    }

    public function updateStatus(EmploymentStatusRequest $request, EmploymentStatus $status): JsonResponse
    {
        $this->employmentStatusService->update($status, $request->validated());

        return response()->json(['id' => $status->id, 'label' => $status->name]);
    }

    public function destroyDepartment(Department $department): JsonResponse
    {
        return $this->deleteResult($this->departmentService->deleteIfUnused($department), 'department');
    }

    public function destroyPosition(Position $position): JsonResponse
    {
        return $this->deleteResult($this->positionService->deleteIfUnused($position), 'position');
    }

    public function destroyStatus(EmploymentStatus $status): JsonResponse
    {
        return $this->deleteResult($this->employmentStatusService->deleteIfUnused($status), 'status');
    }

    private function deleteResult(bool $deleted, string $label): JsonResponse
    {
        if (! $deleted) {
            return response()->json([
                'message' => "This {$label} cannot be deleted because employee records are using it.",
            ], 409);
        }

        return response()->json(['deleted' => true]);
    }
}