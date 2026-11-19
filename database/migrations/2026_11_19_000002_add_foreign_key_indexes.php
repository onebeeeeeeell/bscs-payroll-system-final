<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Purpose: Ang PostgreSQL ay hindi nag-i-index ng foreign key columns
     * nang kusa (MySQL oo). Kaya ang mga JOIN, eager loads (WHERE x_id IN ...),
     * withCount at cascade deletes ay nag-i-scan ng buong table habang lumalaki
     * ito. Dinadagdagan dito ng index ang mga column na madalas i-query.
     *
     * Hindi kasama:
     * - attendances(employee_id, work_date): nasa 2026_11_19_000001 na.
     * - payslips.payroll_id: unique na, may index na.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index('position_id');
            $table->index('department_id');
        });

        // work_date lang: para sa date filter ng attendance list at sa
        // "missing attendance today" count (walang employee_id sa WHERE).
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('work_date');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->index('employee_id');
            $table->index('payroll_period_id');
            $table->index('user_id');
            $table->index('date_processed'); // payroll list: latest('date_processed')
        });

        Schema::table('deductions', function (Blueprint $table) {
            $table->index('payroll_id');
            $table->index('contribution_rate_id');
        });

        Schema::table('contribution_rates', function (Blueprint $table) {
            $table->index('contribution_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('contribution_rates', function (Blueprint $table) {
            $table->dropIndex(['contribution_type_id']);
        });

        Schema::table('deductions', function (Blueprint $table) {
            $table->dropIndex(['payroll_id']);
            $table->dropIndex(['contribution_rate_id']);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['payroll_period_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['date_processed']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['work_date']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['position_id']);
            $table->dropIndex(['department_id']);
        });
    }
};
