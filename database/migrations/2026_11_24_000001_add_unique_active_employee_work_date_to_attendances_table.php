<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'attendances_employee_work_date_active_unique';

    /*
     * Purpose: Isang active (hindi soft-deleted) attendance record lang bawat
     * employee bawat araw, kahit lumampas sa form validation (sabay na submit,
     * direktang request). Partial unique index ito, kaya ang soft-deleted na mga
     * row ay hindi nagba-block. Sinusuportahan ng PostgreSQL at SQLite; sa MySQL
     * ang validation at ang restore check na lang ang proteksyon.
     */
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            return;
        }

        $duplicates = DB::table('attendances')
            ->whereNull('deleted_at')
            ->select('employee_id', 'work_date')
            ->groupBy('employee_id', 'work_date')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        if ($duplicates > 0) {
            throw new RuntimeException(
                "Found {$duplicates} employee/day combination(s) with more than one active attendance record. "
                .'Delete or merge the duplicates, then run the migration again.'
            );
        }

        DB::statement('CREATE UNIQUE INDEX '.self::INDEX.' ON attendances (employee_id, work_date) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS '.self::INDEX);
        }
    }
};
