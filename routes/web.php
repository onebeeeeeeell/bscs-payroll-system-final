<?php


use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollProcessController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PayrollPeriodController;
use App\Http\Controllers\EmployeeOptionController;
use App\Http\Controllers\ContributionRateController;
use App\Http\Controllers\ContributionTypeController;
use App\Http\Controllers\TimeClockController;


Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('time-clock', [TimeClockController::class, 'index'])->name('time-clock.index');
Route::post('time-clock', [TimeClockController::class, 'store'])->middleware('throttle:60,1')->name('time-clock.store');


Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');


    // View in the attendance actions: records of one employee (no single-record show page)
    Route::get('/attendance/employees/{employee}', [AttendanceController::class, 'employeeRecords'])
        ->whereNumber('employee')
        ->name('attendance.employee-records');

    Route::resource('attendance', AttendanceController::class)->except('show');


    Route::patch('/attendance/{attendance}/restore', [AttendanceController::class, 'restore'])
        ->name('attendance.restore');


    Route::resource('employees', EmployeeController::class);
    Route::patch('employees/{employee}/restore', [EmployeeController::class, 'restore'])
        ->name('employees.restore');


    Route::get('search', SearchController::class)->name('search');


    Route::resource('contribution_rates', ContributionRateController::class);
    Route::resource('contribution_types', ContributionTypeController::class);
    Route::resource('departments', DepartmentController::class);
    Route::resource('positions', PositionController::class);
    // Dapat nasa taas ng payrolls resource para hindi mapagkamalang payrolls/{payroll}
    Route::get('payrolls/process', [PayrollProcessController::class, 'create'])->name('payrolls.process.create');
    Route::post('payrolls/process', [PayrollProcessController::class, 'store'])->name('payrolls.process.store');
    Route::resource('payrolls', PayrollController::class)->except(['create', 'store']);
    Route::patch('payrolls/{payroll}/restore', [PayrollController::class, 'restore'])->name('payrolls.restore');
    // Read-only: ang deductions ay awtomatikong ginagawa ng payroll processing
    Route::resource('deductions', DeductionController::class)->only(['index']);
    // View in the payslip list: payslips of one employee (dapat nasa taas ng resource)
    Route::get('payslips/employees/{employee}', [PayslipController::class, 'employeeRecords'])
        ->whereNumber('employee')
        ->name('payslips.employee-records');
    // Walang edit/update: ang payslip ay generate, view, at archive lang
    Route::resource('payslips', PayslipController::class)->except(['edit', 'update']);
    Route::patch('payslips/{payslip}/restore', [PayslipController::class, 'restore'])->name('payslips.restore');
    Route::resource('payroll_periods', PayrollPeriodController::class);
    Route::patch('payroll_periods/{payroll_period}/restore', [PayrollPeriodController::class, 'restore'])->name('payroll_periods.restore');
    Route::post('employee-options/departments', [EmployeeOptionController::class, 'storeDepartment'])->name('employee-options.departments.store');
    Route::patch('employee-options/departments/{department}', [EmployeeOptionController::class, 'updateDepartment'])->name('employee-options.departments.update');
    Route::delete('employee-options/departments/{department}', [EmployeeOptionController::class, 'destroyDepartment'])->name('employee-options.departments.destroy');
    Route::post('employee-options/positions', [EmployeeOptionController::class, 'storePosition'])->name('employee-options.positions.store');
    Route::patch('employee-options/positions/{position}', [EmployeeOptionController::class, 'updatePosition'])->name('employee-options.positions.update');
    Route::delete('employee-options/positions/{position}', [EmployeeOptionController::class, 'destroyPosition'])->name('employee-options.positions.destroy');
    Route::post('employee-options/statuses', [EmployeeOptionController::class, 'storeStatus'])->name('employee-options.statuses.store');
    Route::patch('employee-options/statuses/{status}', [EmployeeOptionController::class, 'updateStatus'])->name('employee-options.statuses.update');
    Route::delete('employee-options/statuses/{status}', [EmployeeOptionController::class, 'destroyStatus'])->name('employee-options.statuses.destroy');
});


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';