<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('seminar_students', function (Blueprint $table) {
            if (! Schema::hasColumn('seminar_students', 'revenue_id')) {
                $table->unsignedBigInteger('revenue_id')->nullable()->after('student_id');
                $table->foreign('revenue_id')->references('id')->on('revenues')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('seminar_students', function (Blueprint $table) {
            if (Schema::hasColumn('seminar_students', 'revenue_id')) {
                $table->dropForeign(['revenue_id']);
                $table->dropColumn('revenue_id');
            }
        });
    }
};
