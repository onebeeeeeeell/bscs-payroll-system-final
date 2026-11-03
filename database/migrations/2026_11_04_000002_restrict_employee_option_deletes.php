<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();

            $table->dropForeign(['position_id']);
            $table->foreign('position_id')->references('id')->on('positions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();

            $table->dropForeign(['position_id']);
            $table->foreign('position_id')->references('id')->on('positions')->cascadeOnDelete();
        });
    }
};
