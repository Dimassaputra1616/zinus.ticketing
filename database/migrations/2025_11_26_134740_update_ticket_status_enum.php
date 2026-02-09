<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL: Use MODIFY with ENUM
            DB::statement("ALTER TABLE tickets MODIFY status ENUM('open','assigned','in_progress','waiting_user','resolved','closed') NOT NULL DEFAULT 'open'");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: Convert to VARCHAR if not already, or use ALTER TYPE
            // Safe approach: use VARCHAR instead of ENUM for PostgreSQL
            $columnType = DB::select("SELECT data_type FROM information_schema.columns WHERE table_name = 'tickets' AND column_name = 'status'")[0]->data_type ?? null;

            if ($columnType === 'USER-DEFINED') {
                // If it's already a custom ENUM type, convert to VARCHAR for simplicity
                DB::statement("ALTER TABLE tickets ALTER COLUMN status TYPE VARCHAR(50)");
                DB::statement("ALTER TABLE tickets ALTER COLUMN status SET DEFAULT 'open'");
            } elseif ($columnType === 'character varying') {
                // Already VARCHAR, just ensure default
                DB::statement("ALTER TABLE tickets ALTER COLUMN status SET DEFAULT 'open'");
            }
            // Note: Values are already in the column, we just ensure it accepts the new values
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL: Revert to old ENUM values
            DB::statement("ALTER TABLE tickets MODIFY status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open'");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: Keep as VARCHAR (safer than trying to revert)
            // Or you could add validation here if needed
            DB::statement("ALTER TABLE tickets ALTER COLUMN status SET DEFAULT 'open'");
        }
    }
};
