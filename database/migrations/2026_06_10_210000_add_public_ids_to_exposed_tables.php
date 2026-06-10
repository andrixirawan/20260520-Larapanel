<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'users',
        'posts',
        'pos_products',
        'pos_product_variants',
        'pos_shifts',
        'pos_sales',
        'pos_sale_items',
        'pos_payments',
        'pos_finance_entries',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'public_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->ulid('public_id')->nullable()->unique()->after('id');
            });

            DB::table($tableName)
                ->whereNull('public_id')
                ->orderBy('id')
                ->select('id')
                ->lazyById()
                ->each(function (object $record) use ($tableName): void {
                    DB::table($tableName)
                        ->where('id', $record->id)
                        ->update(['public_id' => (string) Str::ulid()]);
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'public_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropUnique("{$tableName}_public_id_unique");
                $table->dropColumn('public_id');
            });
        }
    }
};
