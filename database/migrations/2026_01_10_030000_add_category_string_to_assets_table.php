<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'category')) {
                $table->string('category', 100)->nullable()->after('name');
            }
        });

        // Backfill category string from existing category relation if available.
        if (Schema::hasColumn('assets', 'category') && Schema::hasColumn('assets', 'category_id')) {
            $assets = DB::table('assets')->select('id', 'category_id')->whereNull('category')->get();

            if ($assets->isNotEmpty()) {
                $categoryMap = DB::table('categories')->pluck('name', 'id');
                foreach ($assets as $asset) {
                    $name = $categoryMap[$asset->category_id] ?? null;
                    DB::table('assets')->where('id', $asset->id)->update([
                        'category' => $name,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
