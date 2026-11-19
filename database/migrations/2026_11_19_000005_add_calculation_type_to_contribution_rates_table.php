<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * 'fixed'   = flat amount per payroll (rate = PHP amount). Existing rows stay fixed.
     * 'percent' = percentage of the employee's gross pay (rate = percent, e.g. 4.50).
     */
    public function up(): void
    {
        Schema::table('contribution_rates', function (Blueprint $table) {
            $table->string('calculation_type', 10)->default('fixed')->after('contribution_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('contribution_rates', function (Blueprint $table) {
            $table->dropColumn('calculation_type');
        });
    }
};
