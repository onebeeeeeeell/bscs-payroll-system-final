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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
        return $this->deleteIfUnused($department, fn () => $department->employees()->exists(), 'department');
    }

    public function destroyPosition(Position $position): JsonResponse
    {
        return $this->deleteIfUnused($position, fn () => $position->employees()->exists(), 'position');
    }

    public function destroyStatus(EmploymentStatus $status): JsonResponse
    {
        return $this->deleteIfUnused(
            $status,
            fn () => DB::table('employees')->where('status', $status->name)->exists(),
            'status',
        );
    }

    private function deleteIfUnused(Model $model, callable $isInUse, string $label): JsonResponse
    {
        DB::transaction(function () use ($model, $isInUse, $label) {
            $model->newQuery()->whereKey($model->getKey())->lockForUpdate()->firstOrFail();

            if ($isInUse()) {
                throw new HttpResponseException(response()->json([
                    'message' => "This {$label} cannot be deleted because employee records are using it.",
                ], 409));
            }

            $model->delete();
        });

        return response()->json(['deleted' => true]);
    }
}
