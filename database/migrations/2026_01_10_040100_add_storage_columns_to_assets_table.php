<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'storage_gb')) {
                $table->unsignedInteger('storage_gb')->nullable()->after('specs');
            }
            if (! Schema::hasColumn('assets', 'storage_detail')) {
                $table->string('storage_detail')->nullable()->after('storage_gb');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            foreach (['storage_detail', 'storage_gb'] as $column) {
                if (Schema::hasColumn('assets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
