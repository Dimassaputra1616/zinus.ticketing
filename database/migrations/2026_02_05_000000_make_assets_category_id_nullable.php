<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assets') || ! Schema::hasColumn('assets', 'category_id')) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE assets MODIFY category_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE assets ALTER COLUMN category_id DROP NOT NULL');
        } elseif ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE assets ALTER COLUMN category_id BIGINT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('assets') || ! Schema::hasColumn('assets', 'category_id')) {
            return;
        }

        if (DB::table('assets')->whereNull('category_id')->exists()) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE assets MODIFY category_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE assets ALTER COLUMN category_id SET NOT NULL');
        } elseif ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE assets ALTER COLUMN category_id BIGINT NOT NULL');
        }
    }
};
