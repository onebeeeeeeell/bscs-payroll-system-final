<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            // unique kahit naka-archive na: isang review lang bawat email
            $table->string('email', 30)->unique();
            $table->unsignedTinyInteger('rating');
            $table->string('comment', 200);
            // null = naghihintay pa ng approval ng admin
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            // para sa public list: approved, hindi naka-archive, latest first
            $table->index(['approved_at', 'archived_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
