<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $legacyRemoteColumn = 'rust'.'desk_id';

        if (! Schema::hasColumn('assets', $legacyRemoteColumn)) {
            return;
        }

        if (Schema::hasColumn('assets', 'anydesk_id')) {
            DB::table('assets')
                ->where(function ($query) {
                    $query->whereNull('anydesk_id')
                        ->orWhere('anydesk_id', '');
                })
                ->whereNotNull($legacyRemoteColumn)
                ->where($legacyRemoteColumn, '!=', '')
                ->update(['anydesk_id' => DB::raw($legacyRemoteColumn)]);
        }

        Schema::table('assets', function (Blueprint $table) use ($legacyRemoteColumn) {
            $table->dropColumn($legacyRemoteColumn);
        });
    }

    public function down(): void
    {
        $legacyRemoteColumn = 'rust'.'desk_id';

        if (Schema::hasColumn('assets', $legacyRemoteColumn)) {
            return;
        }

        Schema::table('assets', function (Blueprint $table) use ($legacyRemoteColumn) {
            $table->string($legacyRemoteColumn)->nullable()->after('ip_address');
        });
    }
};
