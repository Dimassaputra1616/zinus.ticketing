<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL enforces enum via CHECK constraints under the hood when using enum()
        // Here we drop the implicit constraint and change the column to a string.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tickets DROP CONSTRAINT IF EXISTS tickets_priority_check');
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->string('priority')->default('medium')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting this is difficult because we would need to ensure no 'critical' data exists
        // before converting back to the constraint, so we leave it as a string on down.
    }
};
