<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'factory')) {
                $table->string('factory', 150)->nullable()->after('name');
            }
            if (! Schema::hasColumn('assets', 'brand')) {
                $table->string('brand', 150)->nullable()->after('factory');
            }
            if (! Schema::hasColumn('assets', 'model')) {
                $table->string('model', 150)->nullable()->after('brand');
            }
            if (! Schema::hasColumn('assets', 'specs')) {
                $table->text('specs')->nullable()->after('serial_number');
            }
            if (! Schema::hasColumn('assets', 'price')) {
                $table->decimal('price', 15, 2)->nullable()->after('warranty_expired');
            }
            if (! Schema::hasColumn('assets', 'notes')) {
                $table->text('notes')->nullable()->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            foreach (['notes', 'price', 'specs', 'model', 'brand', 'factory'] as $column) {
                if (Schema::hasColumn('assets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
