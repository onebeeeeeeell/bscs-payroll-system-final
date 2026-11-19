<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Purpose: Mga index para sa search, hire-date filter, at attendance sort.
     * - employees.date_hired: hiredIn() range filter at MIN/MAX sa employees page.
     * - attendances (work_date, time_in): tugma sa latestFirst() (ORDER BY
     *   work_date DESC, time_in DESC). Kaya na rin nito ang work_date-only na
     *   queries (leftmost prefix), kaya pinapalitan ang single-column index.
     *
     * PostgreSQL lang (nilalaktawan sa SQLite):
     * - pg_trgm GIN sa first_name/last_name: ang ILIKE '%term%' ay hindi
     *   magagamit ang normal na B-tree dahil nasa unahan ang wildcard.
     * - Partial index sa active employees: ORDER BY created_at DESC, id DESC
     *   ng employee list, para sa archived_at IS NULL lang.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index('date_hired');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_work_date_index');
            $table->index(['work_date', 'time_in'], 'attendances_work_date_time_in_index');
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX employees_first_name_trgm_index ON employees USING gin (first_name gin_trgm_ops)');
        DB::statement('CREATE INDEX employees_last_name_trgm_index ON employees USING gin (last_name gin_trgm_ops)');
        DB::statement('CREATE INDEX employees_active_created_at_index ON employees (created_at DESC, id DESC) WHERE archived_at IS NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS employees_active_created_at_index');
            DB::statement('DROP INDEX IF EXISTS employees_last_name_trgm_index');
            DB::statement('DROP INDEX IF EXISTS employees_first_name_trgm_index');
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_work_date_time_in_index');
            $table->index('work_date');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['date_hired']);
        });
    }
};
