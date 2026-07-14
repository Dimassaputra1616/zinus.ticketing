<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'assigned_to_name')) {
                $table->string('assigned_to_name')->nullable()->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'assigned_to_name')) {
                $table->dropColumn('assigned_to_name');
            }
        });
    }
};
