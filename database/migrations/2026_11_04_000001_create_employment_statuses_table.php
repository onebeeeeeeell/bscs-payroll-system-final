<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('status')->change();
        });

        Schema::create('employment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->timestamps();
        });

        $now = now();
        DB::table('employment_statuses')->insert([
            ['name' => 'Casual', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Regular', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Permanent', 'created_at' => $now, 'updated_at' => $now],
        ]);

        foreach (DB::table('employees')->distinct()->pluck('status') as $status) {
            DB::table('employment_statuses')->insertOrIgnore([
                'name' => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $customStatusesInUse = DB::table('employees')
            ->whereNotIn('status', ['Casual', 'Regular', 'Permanent'])
            ->distinct()
            ->pluck('status');

        if ($customStatusesInUse->isNotEmpty()) {
            throw new \RuntimeException('Cannot roll back employment statuses while employees use custom status names.');
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->enum('status', ['Casual', 'Regular', 'Permanent'])->change();
        });

        Schema::dropIfExists('employment_statuses');
    }
};
