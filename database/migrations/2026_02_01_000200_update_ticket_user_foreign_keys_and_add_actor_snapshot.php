<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Tickets table
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE tickets MODIFY user_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE tickets ALTER COLUMN user_id DROP NOT NULL');
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Ticket comments table
        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE ticket_comments MODIFY user_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE ticket_comments ALTER COLUMN user_id DROP NOT NULL');
        }

        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Ticket logs table
        Schema::table('ticket_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE ticket_logs MODIFY user_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE ticket_logs ALTER COLUMN user_id DROP NOT NULL');
        }

        Schema::table('ticket_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Add snapshot columns
        Schema::table('ticket_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('ticket_logs', 'actor_name')) {
                $table->string('actor_name')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('ticket_logs', 'actor_email')) {
                $table->string('actor_email')->nullable()->after('actor_name');
            }
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        // Drop snapshot columns
        Schema::table('ticket_logs', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_logs', 'actor_email')) {
                $table->dropColumn('actor_email');
            }
            if (Schema::hasColumn('ticket_logs', 'actor_name')) {
                $table->dropColumn('actor_name');
            }
        });

        // Ticket logs table
        Schema::table('ticket_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE ticket_logs MODIFY user_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE ticket_logs ALTER COLUMN user_id SET NOT NULL');
        }

        Schema::table('ticket_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Ticket comments table
        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE ticket_comments MODIFY user_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE ticket_comments ALTER COLUMN user_id SET NOT NULL');
        }

        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Tickets table
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE tickets MODIFY user_id BIGINT UNSIGNED NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE tickets ALTER COLUMN user_id SET NOT NULL');
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
