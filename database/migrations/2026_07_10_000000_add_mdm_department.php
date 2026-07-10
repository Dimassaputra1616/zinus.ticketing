<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departments')) {
            return;
        }

        $now = now();
        $department = DB::table('departments')->where('name', 'MDM')->first();

        if ($department) {
            DB::table('departments')
                ->where('id', $department->id)
                ->update(['updated_at' => $now]);

            return;
        }

        $this->syncDepartmentSequence();

        DB::table('departments')->insert([
            'name' => 'MDM',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('departments')) {
            return;
        }

        $department = DB::table('departments')->where('name', 'MDM')->first();

        if (! $department || $this->departmentIsUsed((int) $department->id)) {
            return;
        }

        DB::table('departments')
            ->where('id', $department->id)
            ->delete();

        $this->syncDepartmentSequence();
    }

    private function departmentIsUsed(int $departmentId): bool
    {
        $references = [
            ['tickets', 'department_id'],
            ['assets', 'department_id'],
            ['users', 'department_id'],
            ['devices', 'department_id'],
            ['borrow_logs', 'department_id'],
            ['asset_basts', 'department_id'],
        ];

        foreach ($references as [$table, $column]) {
            if (
                Schema::hasTable($table)
                && Schema::hasColumn($table, $column)
                && DB::table($table)->where($column, $departmentId)->exists()
            ) {
                return true;
            }
        }

        return false;
    }

    private function syncDepartmentSequence(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            SELECT setval(
                pg_get_serial_sequence('departments', 'id'),
                COALESCE((SELECT MAX(id) FROM departments), 1),
                (SELECT COUNT(*) FROM departments) > 0
            )
        SQL);
    }
};
