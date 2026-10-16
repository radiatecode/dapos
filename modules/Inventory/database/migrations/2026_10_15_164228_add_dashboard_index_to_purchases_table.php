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
        Schema::table('purchases', function (Blueprint $table) {
            $table->index(['tenant_id', 'status', 'order_date'], 'purchases_tenant_status_date_index');
            $table->dropIndex(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->index(['tenant_id', 'status']);
            $table->dropIndex('purchases_tenant_status_date_index');
        });
    }
};
