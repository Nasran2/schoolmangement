<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Status fields
            $table->boolean('alumni')->default(false)->after('active');
            $table->boolean('leaving_docs_issued')->default(false)->after('alumni');

            // Profile fields
            $table->string('nationality', 60)->default('Sri Lankan')->after('religion');
            $table->string('hear_about_us', 50)->nullable()->after('admission_agree');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['alumni', 'leaving_docs_issued', 'nationality', 'hear_about_us']);
        });
    }
};
