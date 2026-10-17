<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('quick_sale_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150)->nullable();
            $table->decimal('unit_amount', 15, 4);
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('amount', 15, 4);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'quick_sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_sale_items');
    }
};
