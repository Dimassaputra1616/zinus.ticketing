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
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'sync_source')) {
                $table->string('sync_source')->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('assets', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('sync_source');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'last_synced_at')) {
                $table->dropColumn('last_synced_at');
            }
            if (Schema::hasColumn('assets', 'sync_source')) {
                $table->dropColumn('sync_source');
            }
        });
    }
};
