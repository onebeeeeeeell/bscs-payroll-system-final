<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ContributionRateController;
use App\Http\Controllers\ContributionTypeController;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeOptionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationSettingsController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollPeriodController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('attendance', AttendanceController::class);

    // Top bar: Ctrl+K search and notification bell (JSON)
    Route::get('search', SearchController::class)->name('search');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Settings > Organization
    Route::get('settings/organization', [OrganizationSettingsController::class, 'index'])->name('settings.organization');
});

Route::resource('contribution_rates', ContributionRateController::class);
Route::resource('contribution_types', ContributionTypeController::class);
Route::resource('departments', DepartmentController::class);
Route::resource('positions', PositionController::class);
Route::resource('payrolls', PayrollController::class);
Route::resource('deductions', DeductionController::class);
Route::resource('payslips', PayslipController::class);
Route::resource('Payroll_periods', PayrollPeriodController::class);
Route::patch('employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
Route::resource('employees', EmployeeController::class);
Route::post('employee-options/departments', [EmployeeOptionController::class, 'storeDepartment'])->name('employee-options.departments.store');
Route::patch('employee-options/departments/{department}', [EmployeeOptionController::class, 'updateDepartment'])->name('employee-options.departments.update');
Route::delete('employee-options/departments/{department}', [EmployeeOptionController::class, 'destroyDepartment'])->name('employee-options.departments.destroy');
Route::post('employee-options/positions', [EmployeeOptionController::class, 'storePosition'])->name('employee-options.positions.store');
Route::patch('employee-options/positions/{position}', [EmployeeOptionController::class, 'updatePosition'])->name('employee-options.positions.update');
Route::delete('employee-options/positions/{position}', [EmployeeOptionController::class, 'destroyPosition'])->name('employee-options.positions.destroy');
Route::post('employee-options/statuses', [EmployeeOptionController::class, 'storeStatus'])->name('employee-options.statuses.store');
Route::patch('employee-options/statuses/{status}', [EmployeeOptionController::class, 'updateStatus'])->name('employee-options.statuses.update');
Route::delete('employee-options/statuses/{status}', [EmployeeOptionController::class, 'destroyStatus'])->name('employee-options.statuses.destroy');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';