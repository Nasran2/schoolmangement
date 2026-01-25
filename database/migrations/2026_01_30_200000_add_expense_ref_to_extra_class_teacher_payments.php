<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extra_class_teacher_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('expense_id')->nullable()->after('paid_at');
            $table->foreign('expense_id')->references('id')->on('expenses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('extra_class_teacher_payments', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropColumn('expense_id');
        });
    }
};
