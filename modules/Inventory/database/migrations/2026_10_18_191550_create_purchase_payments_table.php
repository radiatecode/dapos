<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purchase payments are a ledger. The purchase paid and due amounts stay as totals of these rows.
     * Existing purchases that already stored a paid amount get one opening row for that amount.
     * Rolling back drops that history. The purchase paid and due columns remain.
     */
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 4);
            $table->string('reference_number', 100)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        DB::table('purchases')
            ->where('paid_amount', '>', 0)
            ->orderBy('id')
            ->chunkById(200, function ($purchases): void {
                $rows = [];

                foreach ($purchases as $purchase) {
                    $rows[] = [
                        'tenant_id' => $purchase->tenant_id,
                        'purchase_id' => $purchase->id,
                        'amount' => $purchase->paid_amount,
                        'reference_number' => null,
                        'note' => null,
                        'paid_at' => $purchase->created_at,
                        'recorded_by' => $purchase->created_by,
                        'created_at' => $purchase->created_at,
                        'updated_at' => $purchase->updated_at,
                    ];
                }

                DB::table('purchase_payments')->insert($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
