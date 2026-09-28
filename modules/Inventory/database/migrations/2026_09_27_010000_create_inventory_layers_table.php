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
        Schema::create('inventory_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('source_item_id')->nullable();
            $table->decimal('received_qty', 15, 4);
            $table->decimal('remaining_qty', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('total_cost', 15, 4);
            $table->timestamp('received_at');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status', 20);
            $table->timestamps();

            $table->index(
                ['tenant_id', 'store_id', 'product_variant_id', 'status', 'received_at', 'id'],
                'inventory_layers_fifo_index',
            );
            $table->unique(
                ['tenant_id', 'source_type', 'source_id', 'source_item_id', 'store_id', 'product_variant_id'],
                'inventory_layers_posting_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_layers');
    }
};
