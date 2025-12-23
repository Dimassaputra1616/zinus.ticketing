<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_sync_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('asset_code')->index();
            $table->string('source_ip')->nullable();
            $table->string('hostname')->nullable();
            $table->string('user_name')->nullable();
            $table->enum('status', ['success', 'failed']);
            $table->string('mode')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->foreign('asset_id')
                ->references('id')
                ->on('assets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_sync_logs');
    }
};
