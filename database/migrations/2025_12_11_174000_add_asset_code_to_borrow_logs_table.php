<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('borrow_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('borrow_logs', 'asset_code')) {
                $table->string('asset_code')->nullable()->after('device_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('borrow_logs', function (Blueprint $table) {
            if (Schema::hasColumn('borrow_logs', 'asset_code')) {
                $table->dropColumn('asset_code');
            }
        });
    }
};
