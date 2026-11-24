<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const INDEX = 'contribution_types_name_lower_unique';

    /*
     * Purpose: Walang dalawang contribution type na magkapareho ang pangalan (hindi
     * sensitive sa laki ng letra), kahit lumampas sa form validation (sabay na
     * submit, direktang request). Expression index ito sa lower(name):
     * sinusuportahan ng PostgreSQL at SQLite; sa MySQL ang validation na lang
     * ang proteksyon. Kapag may duplicate na sa table, titigil ang migration.
     */
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            return;
        }

        $duplicates = DB::table('contribution_types')
            ->groupBy(DB::raw('lower(name)'))
            ->havingRaw('COUNT(*) > 1')
            ->selectRaw('lower(name) AS name')
            ->pluck('name');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Duplicate contribution type names found: '.$duplicates->implode(', ').'. '
                .'Merge or rename them (move their rates to one type first), then run the migration again.'
            );
        }

        DB::statement('CREATE UNIQUE INDEX '.self::INDEX.' ON contribution_types (lower(name))');
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS '.self::INDEX);
        }
    }
};
