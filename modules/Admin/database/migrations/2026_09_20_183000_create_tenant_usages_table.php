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
        Schema::create('tenant_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('feature_id')->constrained('features')->restrictOnDelete();
            $table->unsignedInteger('usage')->default(0);
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->timestamp('updated_at')->nullable();

            $table->unique(['tenant_id', 'feature_id', 'period_start']);
            $table->index(['tenant_id', 'feature_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_usages');
    }
};
