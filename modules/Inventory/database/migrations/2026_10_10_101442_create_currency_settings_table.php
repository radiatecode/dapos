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
        Schema::create('currency_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->restrictOnDelete();
            $table->string('currency_code', 3);
            $table->string('currency_symbol', 16);
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->string('decimal_separator');
            $table->string('thousands_separator');
            $table->string('symbol_position');
            $table->boolean('show_decimals')->default(true);
            $table->boolean('add_space')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_settings');
    }
};
