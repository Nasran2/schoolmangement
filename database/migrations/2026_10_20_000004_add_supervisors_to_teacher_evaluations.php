<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_evaluations') && ! Schema::hasColumn('teacher_evaluations', 'supervisors')) {
            Schema::table('teacher_evaluations', function (Blueprint $table) {
                $table->json('supervisors')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('teacher_evaluations', function (Blueprint $table) {
            $table->dropColumn('supervisors');
        });
    }
};
