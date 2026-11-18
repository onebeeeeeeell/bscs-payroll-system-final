<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Purpose: Payroll processing kumukuha ng attendance totals ng lahat ng
     * employee sa loob ng isang period (WHERE work_date BETWEEN ... GROUP BY
     * employee_id). Ang composite index na ito ang gagamitin ng query na 'yon.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['employee_id', 'work_date'], 'attendances_employee_id_work_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_employee_id_work_date_index');
        });
    }
};
