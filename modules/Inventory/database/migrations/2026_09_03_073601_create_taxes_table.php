<?php

use DA\Admin\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tenant::class)->index()->constrained();
            $table->string('tax_name', 150);
            $table->unsignedTinyInteger('tax_type');
            $table->decimal('tax_rate', 10, 2);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
