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
        Schema::table('pos_shifts', function (Blueprint $table): void {
            $table->foreignId('handover_to_cashier_id')
                ->nullable()
                ->after('closed_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('handover_requested_by')
                ->nullable()
                ->after('handover_to_cashier_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('handover_approved_by')
                ->nullable()
                ->after('handover_requested_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('handover_requested_at')->nullable()->after('closed_at');
            $table->timestamp('handover_approved_at')->nullable()->after('handover_requested_at');
            $table->text('handover_notes')->nullable()->after('notes');

            $table->index(['status', 'handover_to_cashier_id']);
        });

        Schema::table('pos_product_variants', function (Blueprint $table): void {
            $table->decimal('low_stock_threshold', 14, 3)->default(0)->after('allow_backorder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_product_variants', function (Blueprint $table): void {
            $table->dropColumn('low_stock_threshold');
        });

        Schema::table('pos_shifts', function (Blueprint $table): void {
            $table->dropIndex(['status', 'handover_to_cashier_id']);
            $table->dropConstrainedForeignId('handover_approved_by');
            $table->dropConstrainedForeignId('handover_requested_by');
            $table->dropConstrainedForeignId('handover_to_cashier_id');
            $table->dropColumn([
                'handover_requested_at',
                'handover_approved_at',
                'handover_notes',
            ]);
        });
    }
};
