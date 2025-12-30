<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        DB::statement('ALTER TABLE `tickets` MODIFY `user_id` BIGINT UNSIGNED NULL');
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        DB::statement('ALTER TABLE `ticket_comments` MODIFY `user_id` BIGINT UNSIGNED NULL');
        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('ticket_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        DB::statement('ALTER TABLE `ticket_logs` MODIFY `user_id` BIGINT UNSIGNED NULL');
        Schema::table('ticket_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

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
        Schema::table('ticket_logs', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_logs', 'actor_email')) {
                $table->dropColumn('actor_email');
            }
            if (Schema::hasColumn('ticket_logs', 'actor_name')) {
                $table->dropColumn('actor_name');
            }
        });

        Schema::table('ticket_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        DB::statement('ALTER TABLE `ticket_logs` MODIFY `user_id` BIGINT UNSIGNED NOT NULL');
        Schema::table('ticket_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        DB::statement('ALTER TABLE `ticket_comments` MODIFY `user_id` BIGINT UNSIGNED NOT NULL');
        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        DB::statement('ALTER TABLE `tickets` MODIFY `user_id` BIGINT UNSIGNED NOT NULL');
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
