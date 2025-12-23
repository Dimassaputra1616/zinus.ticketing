<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'cpu')) {
                $table->string('cpu', 191)->nullable()->after('model');
            }
            if (! Schema::hasColumn('assets', 'ram_gb')) {
                $table->unsignedInteger('ram_gb')->nullable()->after('cpu');
            }
            if (! Schema::hasColumn('assets', 'os_name')) {
                $table->string('os_name', 150)->nullable()->after('storage_detail');
            }
            if (! Schema::hasColumn('assets', 'ip_address')) {
                $table->string('ip_address', 150)->nullable()->after('os_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            foreach (['ip_address', 'os_name', 'ram_gb', 'cpu'] as $column) {
                if (Schema::hasColumn('assets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
