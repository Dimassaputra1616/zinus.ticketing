<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_basts', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_basts', 'photos')) {
                $table->json('photos')->nullable()->after('notes');
            }
        });

        Schema::table('asset_inspections', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_inspections', 'photos')) {
                $table->json('photos')->nullable()->after('action_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asset_basts', function (Blueprint $table) {
            if (Schema::hasColumn('asset_basts', 'photos')) {
                $table->dropColumn('photos');
            }
        });

        Schema::table('asset_inspections', function (Blueprint $table) {
            if (Schema::hasColumn('asset_inspections', 'photos')) {
                $table->dropColumn('photos');
            }
        });
    }
};
