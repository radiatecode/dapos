<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Purchases previously stored a chosen payment status and a shipping amount.
     * Paid rows keep that status by recording the grand total as paid.
     * Every other row has no stored paid amount, so it becomes unpaid with the grand total due.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('paid_amount', 15, 4)->default(0);
            $table->decimal('due_amount', 15, 4)->default(0);
        });

        DB::table('purchases')->where('payment_status', 'Paid')->update([
            'paid_amount' => DB::raw('grand_total'),
            'due_amount' => 0,
        ]);

        DB::table('purchases')->where('payment_status', '!=', 'Paid')->update([
            'paid_amount' => 0,
            'due_amount' => DB::raw('grand_total'),
            'payment_status' => 'Unpaid',
        ]);

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('shipping_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Restores the shipping column at zero. Paid and due amounts are dropped,
     * and a partial status that was stored only as a label cannot be restored.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('shipping_amount', 15, 4)->default(0);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'due_amount']);
        });
    }
};
