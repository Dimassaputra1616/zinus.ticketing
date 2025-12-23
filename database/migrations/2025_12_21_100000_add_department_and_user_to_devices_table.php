<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete()->after('location');
            }
            if (! Schema::hasColumn('devices', 'assigned_user_id')) {
                $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete()->after('department_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (Schema::hasColumn('devices', 'assigned_user_id')) {
                $table->dropConstrainedForeignId('assigned_user_id');
            }
            if (Schema::hasColumn('devices', 'department_id')) {
                $table->dropConstrainedForeignId('department_id');
            }
        });
    }
};
