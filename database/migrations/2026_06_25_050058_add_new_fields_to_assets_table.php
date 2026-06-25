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
            if (! Schema::hasColumn('assets', 'source_type')) {
                $table->string('source_type', 50)->nullable()->default('manual')->after('sync_source');
            }
            if (! Schema::hasColumn('assets', 'condition')) {
                $table->string('condition', 50)->nullable()->after('status');
            }
            if (! Schema::hasColumn('assets', 'lifecycle_status')) {
                $table->string('lifecycle_status', 50)->nullable()->default('active')->after('condition');
            }
            if (! Schema::hasColumn('assets', 'warranty_until')) {
                $table->date('warranty_until')->nullable()->after('warranty_expired');
            }
        });

        // Set source_type = 'agent' for existing assets synced from agent
        try {
            \Illuminate\Support\Facades\DB::table('assets')
                ->where('sync_source', 'agent')
                ->update(['source_type' => 'agent']);
        } catch (\Exception $e) {
            // Silence if table or column doesn't exist yet or other issues
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            foreach (['source_type', 'condition', 'lifecycle_status', 'warranty_until'] as $col) {
                if (Schema::hasColumn('assets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
