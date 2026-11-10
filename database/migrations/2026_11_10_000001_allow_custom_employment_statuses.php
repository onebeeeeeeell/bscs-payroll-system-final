<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_status_check');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $hasCustomStatuses = DB::table('employees')
            ->whereNotIn('status', ['Casual', 'Regular', 'Permanent'])
            ->exists();

        if ($hasCustomStatuses) {
            throw new RuntimeException('Cannot restore the employment status constraint while employees use custom status names.');
        }

        DB::statement("
            ALTER TABLE employees
            ADD CONSTRAINT employees_status_check
            CHECK (status IN ('Casual', 'Regular', 'Permanent'))
        ");
    }
};
