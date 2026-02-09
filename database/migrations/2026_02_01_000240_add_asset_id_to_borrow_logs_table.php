<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (Schema::hasColumn('borrow_logs', 'device_id')) {
            Schema::table('borrow_logs', function (Blueprint $table) {
                $table->dropForeign(['device_id']);
            });

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE borrow_logs MODIFY device_id BIGINT UNSIGNED NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE borrow_logs ALTER COLUMN device_id DROP NOT NULL');
            }

            Schema::table('borrow_logs', function (Blueprint $table) {
                $table->foreign('device_id')->references('id')->on('devices')->nullOnDelete();
            });
        }

        Schema::table('borrow_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('borrow_logs', 'asset_id')) {
                $table->foreignId('asset_id')->nullable()->after('device_id')->constrained('assets')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        Schema::table('borrow_logs', function (Blueprint $table) {
            if (Schema::hasColumn('borrow_logs', 'asset_id')) {
                $table->dropForeign(['asset_id']);
                $table->dropColumn('asset_id');
            }
        });

        if (Schema::hasColumn('borrow_logs', 'device_id')) {
            Schema::table('borrow_logs', function (Blueprint $table) {
                $table->dropForeign(['device_id']);
            });

            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE borrow_logs MODIFY device_id BIGINT UNSIGNED NOT NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE borrow_logs ALTER COLUMN device_id SET NOT NULL');
            }

            Schema::table('borrow_logs', function (Blueprint $table) {
                $table->foreign('device_id')->references('id')->on('devices')->cascadeOnDelete();
            });
        }
    }
};
