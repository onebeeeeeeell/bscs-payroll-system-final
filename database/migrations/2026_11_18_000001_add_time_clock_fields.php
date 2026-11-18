<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employees', 'pin')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('pin')->nullable();
            });
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->time('time_out')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->time('time_out')->nullable(false)->change();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('pin');
        });
    }
};
