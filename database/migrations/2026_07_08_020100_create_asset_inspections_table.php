<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_number')->unique();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('inspection_type', 30)->default('routine');
            $table->date('inspection_date');
            $table->string('overall_condition', 30)->default('good');
            $table->string('result', 30)->default('passed');
            $table->json('checklist')->nullable();
            $table->text('findings')->nullable();
            $table->text('action_required')->nullable();
            $table->date('next_inspection_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['asset_id', 'inspection_date']);
            $table->index(['inspection_type', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_inspections');
    }
};
