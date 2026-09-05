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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedSmallInteger('grace_days')->default(7)->after('cancel_at_period_end');
            $table->timestamp('grace_ends_at')->nullable()->after('grace_days');
            $table->timestamp('grace_notified_at')->nullable()->after('grace_ends_at');

            $table->index('grace_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['grace_ends_at']);
            $table->dropColumn(['grace_days', 'grace_ends_at', 'grace_notified_at']);
        });
    }
};
