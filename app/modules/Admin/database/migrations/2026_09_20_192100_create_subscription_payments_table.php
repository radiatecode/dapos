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
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->string('payment_method');
            $table->string('transaction_id')->nullable()->unique();
            $table->string('status');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('status');
            $table->index(['tenant_id', 'status']);
            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
