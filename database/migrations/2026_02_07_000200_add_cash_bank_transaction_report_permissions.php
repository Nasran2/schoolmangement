<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach (['reports.cash_transactions.view', 'reports.bank_transactions.view'] as $name) {
            if (! Permission::query()->where('name', $name)->exists()) {
                Permission::create(['name' => $name]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach (['reports.cash_transactions.view', 'reports.bank_transactions.view'] as $name) {
            $perm = Permission::query()->where('name', $name)->first();
            if ($perm) {
                $perm->delete();
            }
        }
    }
};
