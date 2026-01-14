<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('extra_classes', function (Blueprint $table) {
            if (! Schema::hasColumn('extra_classes', 'teacher_payment')) {
                $table->decimal('teacher_payment', 10, 2)->default(0)->after('fee');
            }
        });
    }

    public function down(): void
    {
        Schema::table('extra_classes', function (Blueprint $table) {
            if (Schema::hasColumn('extra_classes', 'teacher_payment')) {
                $table->dropColumn('teacher_payment');
            }
        });
    }
};
