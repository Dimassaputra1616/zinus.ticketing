<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            if (! Schema::hasColumn('ticket_attachments', 'original_name')) {
                $table->string('original_name')->nullable()->after('ticket_id');
            }
            if (! Schema::hasColumn('ticket_attachments', 'stored_name')) {
                $table->string('stored_name')->nullable()->after('original_name');
            }
            if (! Schema::hasColumn('ticket_attachments', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('stored_name');
            }
            if (! Schema::hasColumn('ticket_attachments', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            }
            if (! Schema::hasColumn('ticket_attachments', 'disk')) {
                $table->string('disk')->default('public')->after('file_size');
            }
        });

        $hasOldColumns = Schema::hasColumn('ticket_attachments', 'file_name')
            && Schema::hasColumn('ticket_attachments', 'file_path')
            && Schema::hasColumn('ticket_attachments', 'file_type');

        if ($hasOldColumns) {
            DB::statement("UPDATE ticket_attachments SET original_name = COALESCE(original_name, file_name) WHERE original_name IS NULL");
            DB::statement("UPDATE ticket_attachments SET stored_name = COALESCE(stored_name, file_path) WHERE stored_name IS NULL");
            DB::statement("UPDATE ticket_attachments SET mime_type = COALESCE(mime_type, file_type) WHERE mime_type IS NULL");
            DB::statement("UPDATE ticket_attachments SET disk = COALESCE(disk, 'public') WHERE disk IS NULL");
        }
    }

    public function down(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_attachments', 'disk')) {
                $table->dropColumn('disk');
            }
            if (Schema::hasColumn('ticket_attachments', 'file_size')) {
                $table->dropColumn('file_size');
            }
            if (Schema::hasColumn('ticket_attachments', 'mime_type')) {
                $table->dropColumn('mime_type');
            }
            if (Schema::hasColumn('ticket_attachments', 'stored_name')) {
                $table->dropColumn('stored_name');
            }
            if (Schema::hasColumn('ticket_attachments', 'original_name')) {
                $table->dropColumn('original_name');
            }
        });
    }
};
