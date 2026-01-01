<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revenues', function (Blueprint $table) {
            $table
                ->foreign('student_id')
                ->references('id')
                ->on('students')
                ->nullOnDelete();
        });

        Schema::table('teacher_salary_payments', function (Blueprint $table) {
            $table
                ->foreign('teacher_id')
                ->references('id')
                ->on('teachers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('revenues', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
        });

        Schema::table('teacher_salary_payments', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
        });
    }
};
