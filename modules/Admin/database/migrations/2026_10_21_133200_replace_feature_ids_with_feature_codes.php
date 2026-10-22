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
        $this->replaceFeatureId('plan_features', ['plan_id', 'feature_id'], ['plan_id', 'feature']);
        $this->replaceFeatureId(
            'tenant_usages',
            ['tenant_id', 'feature_id', 'period_start'],
            ['tenant_id', 'feature', 'period_start'],
            ['tenant_id', 'feature_id'],
            ['tenant_id', 'feature'],
        );

        Schema::dropIfExists('features');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('features')) {
            Schema::create('features', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('type');
                $table->string('enforcement')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        $this->restoreFeatureId('plan_features', ['plan_id', 'feature'], ['plan_id', 'feature_id']);
        $this->restoreFeatureId(
            'tenant_usages',
            ['tenant_id', 'feature', 'period_start'],
            ['tenant_id', 'feature_id', 'period_start'],
            ['tenant_id', 'feature'],
            ['tenant_id', 'feature_id'],
        );
    }

    /**
     * @param  list<string>  $oldUnique
     * @param  list<string>  $newUnique
     * @param  list<string>|null  $oldIndex
     * @param  list<string>|null  $newIndex
     */
    private function replaceFeatureId(string $table, array $oldUnique, array $newUnique, ?array $oldIndex = null, ?array $newIndex = null): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'feature_id')) {
            return;
        }

        if (! Schema::hasColumn($table, 'feature')) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->string('feature')->nullable()->after('feature_id');
            });
        }

        if (Schema::hasTable('features')) {
            $codes = DB::table('features')->pluck('code', 'id');

            foreach (DB::table($table)->get(['id', 'feature_id']) as $row) {
                $code = $codes[$row->feature_id] ?? null;

                if (! is_string($code) || $code === '') {
                    DB::table($table)->where('id', $row->id)->delete();

                    continue;
                }

                DB::table($table)->where('id', $row->id)->update(['feature' => $code]);
            }
        }

        DB::table($table)->whereNull('feature')->delete();

        $this->ensureIndex($table, $oldUnique[0]);
        $this->dropForeignKey($table, 'feature_id');
        $this->dropIndexOn($table, $oldUnique, unique: true);

        if ($oldIndex !== null) {
            $this->dropIndexOn($table, $oldIndex);
        }

        $this->dropIndexOn($table, ['feature_id']);

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->dropColumn('feature_id');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE {$table} MODIFY feature VARCHAR(255) NOT NULL");
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table, $newUnique, $newIndex): void {
            if (! $this->hasIndex($table, $newUnique)) {
                $blueprint->unique($newUnique);
            }

            if ($newIndex !== null && ! $this->hasIndex($table, $newIndex)) {
                $blueprint->index($newIndex);
            }
        });
    }

    private function ensureIndex(string $table, string $column): void
    {
        if ($this->hasIndex($table, [$column])) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            $blueprint->index($column);
        });
    }

    private function dropForeignKey(string $table, string $column): void
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if (($foreignKey['columns'] ?? []) !== [$column]) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($foreignKey): void {
                $blueprint->dropForeign($foreignKey['name']);
            });
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropIndexOn(string $table, array $columns, bool $unique = false): void
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['primary'] ?? false) || ($index['columns'] ?? []) !== $columns) {
                continue;
            }

            if (($index['unique'] ?? false) !== $unique) {
                continue;
            }

            $name = $index['name'];

            Schema::table($table, function (Blueprint $blueprint) use ($name, $unique): void {
                if ($unique) {
                    $blueprint->dropUnique($name);

                    return;
                }

                $blueprint->dropIndex($name);
            });
        }
    }

    /**
     * @param  list<string>  $oldUnique
     * @param  list<string>  $newUnique
     * @param  list<string>|null  $oldIndex
     * @param  list<string>|null  $newIndex
     */
    private function restoreFeatureId(string $table, array $oldUnique, array $newUnique, ?array $oldIndex = null, ?array $newIndex = null): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'feature') || Schema::hasColumn($table, 'feature_id')) {
            return;
        }

        $codes = DB::table($table)->distinct()->pluck('feature');

        foreach ($codes as $code) {
            if (! is_string($code) || $code === '') {
                continue;
            }

            DB::table('features')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $code,
                    'type' => 'LIMIT',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->foreignId('feature_id')->nullable()->after('feature')->constrained('features')->restrictOnDelete();
        });

        $ids = DB::table('features')->pluck('id', 'code');

        foreach (DB::table($table)->get(['id', 'feature']) as $row) {
            $featureId = $ids[$row->feature] ?? null;

            if ($featureId === null) {
                DB::table($table)->where('id', $row->id)->delete();

                continue;
            }

            DB::table($table)->where('id', $row->id)->update(['feature_id' => $featureId]);
        }

        Schema::table($table, function (Blueprint $blueprint) use ($oldUnique, $oldIndex): void {
            $blueprint->dropUnique($oldUnique);

            if ($oldIndex !== null) {
                $blueprint->dropIndex($oldIndex);
            }

            $blueprint->dropColumn('feature');
        });

        Schema::table($table, function (Blueprint $blueprint) use ($newUnique, $newIndex): void {
            $blueprint->unique($newUnique);

            if ($newIndex !== null) {
                $blueprint->index($newIndex);
            }
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasIndex(string $table, array $columns): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $index) {
            if (($index['columns'] ?? []) === $columns) {
                return true;
            }
        }

        return false;
    }
};
