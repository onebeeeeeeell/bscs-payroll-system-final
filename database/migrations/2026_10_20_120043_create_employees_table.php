<?php

use App\Models\Position;
use App\Models\Department;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->date('date_hired');
            $table->decimal('daily_rate', 10, 2);
            $table->string('sss_no', 20)->nullable()->unique();
            $table->string('philhealth_no', 20)->nullable()->unique();
            $table->string('pagibig_no', 20)->nullable()->unique();
            $table->string('tin_no', 20)->nullable()->unique();
            $table->enum('status', ['Casual', 'Regular', 'Permanent']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
