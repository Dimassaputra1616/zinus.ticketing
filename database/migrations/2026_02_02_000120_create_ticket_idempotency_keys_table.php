<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->string('idempotency_key', 64);
            $table->string('payload_hash', 64);
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'idempotency_key']);
            $table->index(['session_id', 'idempotency_key']);
            $table->index(['payload_hash', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_idempotency_keys');
    }
};
