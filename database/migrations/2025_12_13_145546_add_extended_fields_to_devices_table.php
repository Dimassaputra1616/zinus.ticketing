<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'purchased_date')) {
                $table->date('purchased_date')->nullable()->after('status');
            }
            if (! Schema::hasColumn('devices', 'acc_num')) {
                $table->string('acc_num', 100)->nullable()->after('purchased_date');
            }
            if (! Schema::hasColumn('devices', 'batch_number')) {
                $table->string('batch_number', 100)->nullable()->after('acc_num');
            }
            if (! Schema::hasColumn('devices', 'company')) {
                $table->string('company', 100)->nullable()->after('batch_number');
            }
            if (! Schema::hasColumn('devices', 'location')) {
                $table->string('location')->nullable()->after('company');
            }
            if (! Schema::hasColumn('devices', 'sub_department')) {
                $table->string('sub_department', 150)->nullable()->after('location');
            }
            if (! Schema::hasColumn('devices', 'mac_address')) {
                $table->string('mac_address', 150)->nullable()->after('sub_department');
            }
            if (! Schema::hasColumn('devices', 'device_name')) {
                $table->string('device_name', 150)->nullable()->after('mac_address');
            }
            if (! Schema::hasColumn('devices', 'brand_model')) {
                $table->string('brand_model', 150)->nullable()->after('device_name');
            }
            if (! Schema::hasColumn('devices', 'board')) {
                $table->string('board', 150)->nullable()->after('brand_model');
            }
            if (! Schema::hasColumn('devices', 'inventory_check')) {
                $table->string('inventory_check', 50)->nullable()->after('board');
            }
            if (! Schema::hasColumn('devices', 'asset_no')) {
                $table->string('asset_no', 150)->nullable()->after('inventory_check');
            }
            if (! Schema::hasColumn('devices', 'updated_custom_at')) {
                $table->date('updated_custom_at')->nullable()->after('asset_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            foreach ([
                'updated_custom_at',
                'asset_no',
                'inventory_check',
                'board',
                'brand_model',
                'device_name',
                'mac_address',
                'sub_department',
                'company',
                'batch_number',
                'acc_num',
                'purchased_date',
            ] as $column) {
                if (Schema::hasColumn('devices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
