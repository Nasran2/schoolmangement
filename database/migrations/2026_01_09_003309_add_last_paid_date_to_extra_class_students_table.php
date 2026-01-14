<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('extra_class_students', function (Blueprint $table) {
            $table->integer('paid_days')->default(0)->after('paid');
            $table->date('enrolled_at')->nullable()->after('paid_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('extra_class_students', function (Blueprint $table) {
            $table->dropColumn(['paid_days', 'enrolled_at']);
        });
    }
};
