<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendances')) {
            return;
        }

        $this->ensureTimeColumn('attendances', 'check_in_time');
        $this->ensureTimeColumn('attendances', 'check_out_time');
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendances')) {
            return;
        }

        $this->ensureDateTimeColumn('attendances', 'check_in_time');
        $this->ensureDateTimeColumn('attendances', 'check_out_time');
    }

    private function ensureTimeColumn(string $table, string $column): void
    {
        if (!Schema::hasColumn($table, $column)) {
            return;
        }

        $type = $this->columnType($table, $column);
        if (preg_match('/^time(\(|$)/i', $type) === 1) {
            return;
        }

        DB::statement("UPDATE `{$table}` SET `{$column}` = TIME(`{$column}`) WHERE `{$column}` IS NOT NULL");
        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` TIME NULL");
    }

    private function ensureDateTimeColumn(string $table, string $column): void
    {
        if (!Schema::hasColumn($table, $column)) {
            return;
        }

        $type = $this->columnType($table, $column);
        if (preg_match('/^datetime(\(|$)/i', $type) === 1) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` DATETIME NULL");
    }

    private function columnType(string $table, string $column): string
    {
        $result = DB::selectOne(
            "SELECT DATA_TYPE AS data_type FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1",
            [$table, $column]
        );

        return strtolower($result?->data_type ?? '');
    }
};
