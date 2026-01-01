<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->boolean('epf_enabled')->default(true)->after('salary_components');
            $table->boolean('etf_enabled')->default(true)->after('epf_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['epf_enabled', 'etf_enabled']);
        });
    }
};
