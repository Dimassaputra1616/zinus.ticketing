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
            $table->index('user_id');
            $table->index('is_open');
            $table->index('updated_at');
            $table->index(['is_open', 'updated_at']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index('conversation_id');
            $table->index('user_id');
            $table->index('is_read');
            $table->index(['conversation_id', 'created_at']);
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
