<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /*
     * Purpose: Para magamit ng navbar search (Employee::matchingSearchTerm) ang
     * index sa "first last" na bahagi. Kapag may isang OR branch na walang
     * index, seq scan pa rin ang buong query; kaya kailangan ng expression
     * index na kapareho ng (first_name || ' ' || last_name) sa scope.
     * PostgreSQL lang; nangangailangan ng pg_trgm (nasa 2026_11_19_000003).
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement("CREATE INDEX employees_full_name_trgm_index ON employees USING gin ((first_name || ' ' || last_name) gin_trgm_ops)");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS employees_full_name_trgm_index');
        }
    }
};
