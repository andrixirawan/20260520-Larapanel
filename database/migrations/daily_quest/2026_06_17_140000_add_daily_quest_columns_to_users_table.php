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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('timezone')->default('UTC')->after('password');
            $table->unsignedInteger('total_points')->default(0)->after('timezone');
            $table->unsignedInteger('current_streak')->default(0)->after('total_points');
            $table->unsignedInteger('longest_streak')->default(0)->after('current_streak');
            $table->date('last_active_date')->nullable()->after('longest_streak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'timezone',
                'total_points',
                'current_streak',
                'longest_streak',
                'last_active_date',
            ]);
        });
    }
};
