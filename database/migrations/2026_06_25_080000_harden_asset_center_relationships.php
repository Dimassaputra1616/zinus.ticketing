<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->cleanupAssetRelations();
        $this->hardenAssetCategoryForeignKey();
        $this->hardenAssetLogForeignKey();
        $this->addOperationalIndexes();
        $this->addPartialUniqueIndexes();
        $this->addCheckConstraints();
    }

    public function down(): void
    {
        $this->dropCheckConstraints();
        $this->dropPartialUniqueIndexes();
        $this->dropOperationalIndexes();
        $this->restoreAssetLogForeignKey();
        $this->restoreAssetCategoryForeignKey();
    }

    private function cleanupAssetRelations(): void
    {
        if (! Schema::hasTable('asset_relations')) {
            return;
        }

        DB::table('asset_relations')
            ->whereColumn('parent_asset_id', 'child_asset_id')
            ->delete();

        $activeRows = DB::table('asset_relations')
            ->whereNull('ended_at')
            ->orderBy('child_asset_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'child_asset_id', 'notes']);

        $seenChildren = [];
        foreach ($activeRows as $row) {
            if (! isset($seenChildren[$row->child_asset_id])) {
                $seenChildren[$row->child_asset_id] = true;
                continue;
            }

            DB::table('asset_relations')
                ->where('id', $row->id)
                ->update([
                    'ended_at' => now(),
                    'notes' => trim(((string) $row->notes) . "\nEnded automatically while enforcing one active parent per child asset."),
                    'updated_at' => now(),
                ]);
        }
    }

    private function hardenAssetCategoryForeignKey(): void
    {
        if (! Schema::hasTable('assets') || ! Schema::hasColumn('assets', 'category_id')) {
            return;
        }

        if ($this->driver() === 'sqlite') {
            return;
        }

        Schema::table('assets', function (Blueprint $table) {
            $this->dropForeignIfExists($table, 'assets', 'assets_category_id_foreign', ['category_id']);
            $table->foreign('category_id', 'assets_category_id_foreign')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });
    }

    private function hardenAssetLogForeignKey(): void
    {
        if (! Schema::hasTable('asset_logs') || ! Schema::hasColumn('asset_logs', 'asset_id')) {
            return;
        }

        if ($this->driver() === 'sqlite') {
            return;
        }

        Schema::table('asset_logs', function (Blueprint $table) {
            $this->dropForeignIfExists($table, 'asset_logs', 'asset_logs_asset_id_foreign', ['asset_id']);
        });

        $this->makeColumnNullable('asset_logs', 'asset_id');

        Schema::table('asset_logs', function (Blueprint $table) {
            $table->foreign('asset_id', 'asset_logs_asset_id_foreign')
                ->references('id')
                ->on('assets')
                ->nullOnDelete();
        });
    }

    private function restoreAssetCategoryForeignKey(): void
    {
        if (! Schema::hasTable('assets') || ! Schema::hasColumn('assets', 'category_id')) {
            return;
        }

        if ($this->driver() === 'sqlite') {
            return;
        }

        Schema::table('assets', function (Blueprint $table) {
            $this->dropForeignIfExists($table, 'assets', 'assets_category_id_foreign', ['category_id']);
            $table->foreign('category_id', 'assets_category_id_foreign')
                ->references('id')
                ->on('categories')
                ->cascadeOnDelete();
        });
    }

    private function restoreAssetLogForeignKey(): void
    {
        if (! Schema::hasTable('asset_logs') || ! Schema::hasColumn('asset_logs', 'asset_id')) {
            return;
        }

        if ($this->driver() === 'sqlite') {
            return;
        }

        Schema::table('asset_logs', function (Blueprint $table) {
            $this->dropForeignIfExists($table, 'asset_logs', 'asset_logs_asset_id_foreign', ['asset_id']);
            $table->foreign('asset_id', 'asset_logs_asset_id_foreign')
                ->references('id')
                ->on('assets')
                ->cascadeOnDelete();
        });
    }

    private function addOperationalIndexes(): void
    {
        $this->addIndexIfMissing('assets', ['department_id', 'status'], 'assets_department_status_idx');
        $this->addIndexIfMissing('assets', ['user_id', 'status'], 'assets_user_status_idx');
        $this->addIndexIfMissing('assets', ['source_type', 'category', 'status'], 'assets_source_category_status_idx');
        $this->addIndexIfMissing('assets', ['lifecycle_status', 'status'], 'assets_lifecycle_status_idx');

        $this->addIndexIfMissing('asset_relations', ['parent_asset_id', 'ended_at'], 'asset_relations_parent_ended_idx');
        $this->addIndexIfMissing('asset_relations', ['child_asset_id', 'ended_at'], 'asset_relations_child_ended_idx');

        $this->addIndexIfMissing('borrow_logs', ['asset_id', 'status', 'deleted_at'], 'borrow_logs_asset_status_deleted_idx');
        $this->addIndexIfMissing('borrow_logs', ['asset_code', 'status', 'deleted_at'], 'borrow_logs_asset_code_status_deleted_idx');
        $this->addIndexIfMissing('borrow_logs', ['user_id', 'status', 'deleted_at'], 'borrow_logs_user_status_deleted_idx');

        $this->addIndexIfMissing('asset_logs', ['asset_id', 'created_at'], 'asset_logs_asset_created_idx');
        $this->addIndexIfMissing('asset_sync_logs', ['asset_id', 'created_at'], 'asset_sync_logs_asset_created_idx');
    }

    private function dropOperationalIndexes(): void
    {
        $this->dropIndexIfExists('asset_sync_logs', 'asset_sync_logs_asset_created_idx');
        $this->dropIndexIfExists('asset_logs', 'asset_logs_asset_created_idx');

        $this->dropIndexIfExists('borrow_logs', 'borrow_logs_user_status_deleted_idx');
        $this->dropIndexIfExists('borrow_logs', 'borrow_logs_asset_code_status_deleted_idx');
        $this->dropIndexIfExists('borrow_logs', 'borrow_logs_asset_status_deleted_idx');

        $this->dropIndexIfExists('asset_relations', 'asset_relations_child_ended_idx');
        $this->dropIndexIfExists('asset_relations', 'asset_relations_parent_ended_idx');

        $this->dropIndexIfExists('assets', 'assets_lifecycle_status_idx');
        $this->dropIndexIfExists('assets', 'assets_source_category_status_idx');
        $this->dropIndexIfExists('assets', 'assets_user_status_idx');
        $this->dropIndexIfExists('assets', 'assets_department_status_idx');
    }

    private function addPartialUniqueIndexes(): void
    {
        if (! Schema::hasTable('asset_relations')) {
            return;
        }

        if (! in_array($this->driver(), ['pgsql', 'sqlite'], true)) {
            return;
        }

        if (! $this->indexExists('asset_relations', 'asset_relations_active_child_unique')) {
            DB::statement('CREATE UNIQUE INDEX asset_relations_active_child_unique ON asset_relations (child_asset_id) WHERE ended_at IS NULL');
        }
    }

    private function dropPartialUniqueIndexes(): void
    {
        $this->dropIndexIfExists('asset_relations', 'asset_relations_active_child_unique');
    }

    private function addCheckConstraints(): void
    {
        if ($this->driver() !== 'pgsql') {
            return;
        }

        if (Schema::hasTable('asset_relations') && ! $this->constraintExists('asset_relations', 'asset_relations_no_self_check')) {
            DB::statement('ALTER TABLE asset_relations ADD CONSTRAINT asset_relations_no_self_check CHECK (parent_asset_id <> child_asset_id)');
        }

        if (
            Schema::hasTable('borrow_logs')
            && Schema::hasColumn('borrow_logs', 'asset_id')
            && Schema::hasColumn('borrow_logs', 'device_id')
            && ! $this->constraintExists('borrow_logs', 'borrow_logs_asset_or_device_check')
            && ! DB::table('borrow_logs')->whereNull('asset_id')->whereNull('device_id')->exists()
        ) {
            DB::statement('ALTER TABLE borrow_logs ADD CONSTRAINT borrow_logs_asset_or_device_check CHECK (asset_id IS NOT NULL OR device_id IS NOT NULL)');
        }
    }

    private function dropCheckConstraints(): void
    {
        if ($this->driver() !== 'pgsql') {
            return;
        }

        if (Schema::hasTable('borrow_logs') && $this->constraintExists('borrow_logs', 'borrow_logs_asset_or_device_check')) {
            DB::statement('ALTER TABLE borrow_logs DROP CONSTRAINT borrow_logs_asset_or_device_check');
        }

        if (Schema::hasTable('asset_relations') && $this->constraintExists('asset_relations', 'asset_relations_no_self_check')) {
            DB::statement('ALTER TABLE asset_relations DROP CONSTRAINT asset_relations_no_self_check');
        }
    }

    private function addIndexIfMissing(string $table, array $columns, string $index): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $index)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $index) {
            $tableBlueprint->index($columns, $index);
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $index)) {
            return;
        }

        if (in_array($this->driver(), ['pgsql', 'sqlite'], true)) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
            return;
        }

        if ($this->driver() === 'mysql') {
            DB::statement("DROP INDEX {$index} ON {$table}");
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($index) {
            $tableBlueprint->dropIndex($index);
        });
    }

    private function dropForeignIfExists(Blueprint $table, string $tableName, string $foreignName, array $columns): void
    {
        if (! $this->foreignKeyExists($tableName, $foreignName)) {
            return;
        }

        try {
            $table->dropForeign($columns);
        } catch (Throwable) {
            try {
                $table->dropForeign($foreignName);
            } catch (Throwable) {
                //
            }
        }
    }

    private function makeColumnNullable(string $table, string $column): void
    {
        match ($this->driver()) {
            'pgsql' => DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} DROP NOT NULL"),
            'mysql' => DB::statement("ALTER TABLE {$table} MODIFY {$column} BIGINT UNSIGNED NULL"),
            'sqlsrv' => DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} BIGINT NULL"),
            default => null,
        };
    }

    private function indexExists(string $table, string $index): bool
    {
        return match ($this->driver()) {
            'pgsql' => (bool) DB::selectOne(
                'select 1 from pg_indexes where schemaname = current_schema() and tablename = ? and indexname = ?',
                [$table, $index]
            ),
            'sqlite' => collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn ($row) => ($row->name ?? null) === $index),
            'mysql' => (bool) DB::selectOne("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]),
            'sqlsrv' => (bool) DB::selectOne(
                'select 1 from sys.indexes where name = ? and object_id = object_id(?)',
                [$index, $table]
            ),
            default => false,
        };
    }

    private function foreignKeyExists(string $table, string $foreignName): bool
    {
        return match ($this->driver()) {
            'pgsql' => (bool) DB::selectOne(
                'select 1 from information_schema.table_constraints where table_schema = current_schema() and table_name = ? and constraint_name = ? and constraint_type = ?',
                [$table, $foreignName, 'FOREIGN KEY']
            ),
            'mysql' => (bool) DB::selectOne(
                'select 1 from information_schema.table_constraints where constraint_schema = database() and table_name = ? and constraint_name = ? and constraint_type = ?',
                [$table, $foreignName, 'FOREIGN KEY']
            ),
            'sqlsrv' => (bool) DB::selectOne(
                'select 1 from sys.foreign_keys where name = ? and parent_object_id = object_id(?)',
                [$foreignName, $table]
            ),
            default => false,
        };
    }

    private function constraintExists(string $table, string $constraint): bool
    {
        return (bool) DB::selectOne(
            'select 1 from information_schema.table_constraints where table_schema = current_schema() and table_name = ? and constraint_name = ?',
            [$table, $constraint]
        );
    }

    private function driver(): string
    {
        return Schema::getConnection()->getDriverName();
    }
};
