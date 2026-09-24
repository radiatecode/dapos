<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->string('enforcement')->nullable()->after('type');
            $table->index('enforcement');
        });

        DB::table('features')
            ->whereIn('code', ['users', 'locations', 'products'])
            ->update(['enforcement' => 'RESOURCE']);

        DB::table('features')
            ->where('type', 'LIMIT')
            ->whereNull('enforcement')
            ->update(['enforcement' => 'CONSUMPTION']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropIndex(['enforcement']);
            $table->dropColumn('enforcement');
        });
    }
};
