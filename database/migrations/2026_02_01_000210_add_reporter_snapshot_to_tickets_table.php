<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'reporter_name')) {
                $table->string('reporter_name')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('tickets', 'reporter_email')) {
                $table->string('reporter_email')->nullable()->after('reporter_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'reporter_email')) {
                $table->dropColumn('reporter_email');
            }
            if (Schema::hasColumn('tickets', 'reporter_name')) {
                $table->dropColumn('reporter_name');
            }
        });
    }
};
