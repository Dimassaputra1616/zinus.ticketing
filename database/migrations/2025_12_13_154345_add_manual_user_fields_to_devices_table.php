<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'user_name')) {
                $table->string('user_name', 150)->nullable()->after('assigned_user_id');
            }
            if (! Schema::hasColumn('devices', 'user_email')) {
                $table->string('user_email', 191)->nullable()->after('user_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (Schema::hasColumn('devices', 'user_email')) {
                $table->dropColumn('user_email');
            }
            if (Schema::hasColumn('devices', 'user_name')) {
                $table->dropColumn('user_name');
            }
        });
    }
};
