<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('category_id')->nullable()->constrained('task_categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon', 64)->nullable();
            $table->string('color', 24)->nullable();
            $table->unsignedInteger('points')->default(10);
            $table->string('recurrence_type', 32)->index();
            $table->json('recurrence_days')->nullable();
            $table->date('recurrence_ends_at')->nullable();
            $table->date('recurrence_starts_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
