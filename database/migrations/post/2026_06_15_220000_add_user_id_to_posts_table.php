<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->foreignId('user_id')
                ->nullable()
                ->after('public_id')
                ->constrained()
                ->nullOnDelete();
        });

        DB::table('users')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->each(function (object $user): void {
                DB::table('posts')
                    ->whereNull('user_id')
                    ->where('author', $user->name)
                    ->update(['user_id' => $user->id]);
            });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
