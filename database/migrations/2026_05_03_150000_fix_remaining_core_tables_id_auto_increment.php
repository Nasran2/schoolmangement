<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['expenses', 'audit_logs'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $column = DB::selectOne(
                "SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tableName}' AND COLUMN_NAME = 'id'"
            );

            if (! $column || str_contains((string) $column->EXTRA, 'auto_increment')) {
                continue;
            }

            DB::statement("ALTER TABLE `{$tableName}` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
        }
    }

    public function down(): void
    {
        foreach (['expenses', 'audit_logs'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $column = DB::selectOne(
                "SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tableName}' AND COLUMN_NAME = 'id'"
            );

            if (! $column || ! str_contains((string) $column->EXTRA, 'auto_increment')) {
                continue;
            }

            DB::statement("ALTER TABLE `{$tableName}` MODIFY `id` BIGINT UNSIGNED NOT NULL");
        }
    }
};
