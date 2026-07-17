<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'monitoring_status')) {
                $table->string('monitoring_status', 30)->nullable()->after('last_synced_at');
            }
            if (! Schema::hasColumn('assets', 'monitoring_checked_at')) {
                $table->timestamp('monitoring_checked_at')->nullable()->after('monitoring_status');
            }
            if (! Schema::hasColumn('assets', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('monitoring_checked_at');
            }
            if (! Schema::hasColumn('assets', 'monitoring_latency_ms')) {
                $table->unsignedInteger('monitoring_latency_ms')->nullable()->after('last_seen_at');
            }
            if (! Schema::hasColumn('assets', 'monitoring_error')) {
                $table->text('monitoring_error')->nullable()->after('monitoring_latency_ms');
            }
            if (! Schema::hasColumn('assets', 'monitoring_source')) {
                $table->string('monitoring_source', 50)->nullable()->after('monitoring_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            foreach ([
                'monitoring_status',
                'monitoring_checked_at',
                'last_seen_at',
                'monitoring_latency_ms',
                'monitoring_error',
                'monitoring_source',
            ] as $column) {
                if (Schema::hasColumn('assets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
