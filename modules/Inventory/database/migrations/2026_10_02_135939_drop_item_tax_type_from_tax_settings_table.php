<?php

use DA\Inventory\Enums\ItemTaxType;
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
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->dropColumn('item_tax_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('item_tax_type')
                ->nullable()
                ->default(ItemTaxType::EXCLUSIVE->value)
                ->after('tax_mode');
        });
    }
};
