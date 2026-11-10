<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\EmploymentStatus;
use App\Models\Position;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeOptionController extends Controller
{
    public function storeDepartment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u', 'unique:departments,department_name'],
        ]);
        $department = Department::create($validated);

        return response()->json(['id' => $department->id, 'label' => $department->department_name], 201);
    }

    public function storePosition(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', 'regex:/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u', 'unique:positions,title'],
        ]);
        $position = Position::create($validated);

        return response()->json(['id' => $position->id, 'label' => $position->title], 201);
    }

    public function storeStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u', Rule::unique('employment_statuses', 'name')],
        ]);
        $status = EmploymentStatus::create($validated);

        return response()->json(['id' => $status->id, 'label' => $status->name], 201);
    }

    public function updateDepartment(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'department_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u', Rule::unique('departments', 'department_name')->ignore($department)],
        ]);
        $department->update($validated);

        return response()->json(['id' => $department->id, 'label' => $department->department_name]);
    }

    public function updatePosition(Request $request, Position $position): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', 'regex:/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u', Rule::unique('positions', 'title')->ignore($position)],
        ]);
        $position->update($validated);

        return response()->json(['id' => $position->id, 'label' => $position->title]);
    }

    public function updateStatus(Request $request, EmploymentStatus $status): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u', Rule::unique('employment_statuses', 'name')->ignore($status)],
        ]);

        DB::transaction(function () use ($status, $validated) {
            $oldName = $status->name;
            $status->update($validated);
            DB::table('employees')->where('status', $oldName)->update(['status' => $status->name]);
        });

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
