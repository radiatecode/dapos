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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->string('movement_type', 40);
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->string('reference_type', 40);
            $table->unsignedBigInteger('reference_id');
            $table->unsignedBigInteger('reference_item_id')->nullable();
            $table->foreignId('inventory_layer_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->string('direction', 10);
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(
                ['tenant_id', 'store_id', 'product_variant_id', 'occurred_at'],
                'stock_movements_history_index',
            );
            $table->unique(
                ['tenant_id', 'store_id', 'reference_type', 'reference_id', 'reference_item_id', 'direction', 'inventory_layer_id'],
                'stock_movements_idempotency_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
