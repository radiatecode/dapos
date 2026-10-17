<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number', 50);
            $table->date('sale_date');
            $table->unsignedInteger('total_items');
            $table->decimal('grand_total', 15, 4);
            $table->unsignedTinyInteger('payment_method');
            $table->decimal('received_amount', 15, 4);
            $table->decimal('change_amount', 15, 4)->default(0);
            $table->string('reference_number', 100)->nullable();
            $table->string('note', 255)->nullable();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_number']);
            $table->index(['tenant_id', 'sale_date']);
            $table->index(['tenant_id', 'store_id', 'sale_date']);
            $table->index(['tenant_id', 'payment_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_sales');
    }
};
