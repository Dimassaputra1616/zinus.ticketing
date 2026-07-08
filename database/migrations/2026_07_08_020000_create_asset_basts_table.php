<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_basts', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('borrow_log_id')->nullable()->constrained('borrow_logs')->nullOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('bast_type', 30)->default('handover');
            $table->string('status', 30)->default('issued');
            $table->date('bast_date');
            $table->string('recipient_name');
            $table->string('recipient_email')->nullable();
            $table->string('recipient_department')->nullable();
            $table->string('handover_location')->nullable();
            $table->string('condition_summary')->nullable();
            $table->json('accessories')->nullable();
            $table->json('asset_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['asset_id', 'bast_date']);
            $table->index(['bast_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_basts');
    }
};
