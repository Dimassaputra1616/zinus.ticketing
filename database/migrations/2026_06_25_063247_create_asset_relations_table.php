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
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'sub_category')) {
                $table->string('sub_category', 100)->nullable()->after('category');
            }
        });

        if (! Schema::hasTable('asset_relations')) {
            Schema::create('asset_relations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_asset_id')->constrained('assets')->onDelete('cascade');
                $table->foreignId('child_asset_id')->constrained('assets')->onDelete('cascade');
                $table->string('relation_type')->default('attached');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_relations');

        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'sub_category')) {
                $table->dropColumn('sub_category');
            }
        });
    }
};
