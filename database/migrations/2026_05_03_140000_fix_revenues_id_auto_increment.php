<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('revenues')) {
            return;
        }

        $column = DB::selectOne(
            "SELECT EXTRA, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'revenues' AND COLUMN_NAME = 'id'"
        );

        if (! $column) {
            return;
        }

        if (str_contains((string) $column->EXTRA, 'auto_increment')) {
            return;
        }

        DB::statement('ALTER TABLE `revenues` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('revenues')) {
            return;
        }

        $column = DB::selectOne(
            "SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'revenues' AND COLUMN_NAME = 'id'"
        );

        if (! $column || ! str_contains((string) $column->EXTRA, 'auto_increment')) {
            return;
        }

        DB::statement('ALTER TABLE `revenues` MODIFY `id` BIGINT UNSIGNED NOT NULL');
    }
};
