<?php

use DA\Admin\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tenant::class)->index()->constrained();
            $table->unsignedTinyInteger('tax_mode')->default(1);
            $table->unsignedTinyInteger('item_tax_type')->nullable();
            $table->unsignedTinyInteger('tax_calculation_base')->default(2);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
