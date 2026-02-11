<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Check if index exists before creating it
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('conversations');

            if (!array_key_exists('conversations_user_id_index', $indexes)) {
                $table->index('user_id');
            }
            if (!array_key_exists('conversations_is_open_index', $indexes)) {
                $table->index('is_open');
            }
            if (!array_key_exists('conversations_updated_at_index', $indexes)) {
                $table->index('updated_at');
            }
            if (!array_key_exists('conversations_is_open_updated_at_index', $indexes)) {
                $table->index(['is_open', 'updated_at']);
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('messages');

            if (!array_key_exists('messages_conversation_id_index', $indexes)) {
                $table->index('conversation_id');
            }
            if (!array_key_exists('messages_user_id_index', $indexes)) {
                $table->index('user_id');
            }
            if (!array_key_exists('messages_is_read_index', $indexes)) {
                $table->index('is_read');
            }
            if (!array_key_exists('messages_conversation_id_created_at_index', $indexes)) {
                $table->index(['conversation_id', 'created_at']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['is_open']);
            $table->dropIndex(['updated_at']);
            $table->dropIndex(['is_open', 'updated_at']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['conversation_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['is_read']);
            $table->dropIndex(['conversation_id', 'created_at']);
        });
    }
};
