<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_month_fee_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('revenue_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedSmallInteger('month'); // 1-12
            $table->unsignedSmallInteger('year');
            $table->enum('type', ['due', 'advance']);
            $table->decimal('applied_amount', 10, 2);
            $table->boolean('is_partial')->default(false);
            $table->decimal('remaining_for_month', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['revenue_id', 'student_id', 'month', 'year'], 'uniq_revenue_student_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_month_fee_allocations');
    }
};
