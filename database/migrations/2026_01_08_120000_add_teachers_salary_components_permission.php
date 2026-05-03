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

        $name = 'teachers.salary.components';
        Permission::findOrCreate($name, 'web');
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        $name = 'teachers.salary.components';
        $perm = Permission::query()
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->first();
        if ($perm) {
            $perm->delete();
        }
    }
};
