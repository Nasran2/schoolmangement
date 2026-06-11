<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_salary_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['teacher_id', 'paid_at']);
        });

        Schema::create('teacher_salary_advance_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_salary_advance_id');
            $table->unsignedBigInteger('teacher_salary_payment_id');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->index('teacher_salary_payment_id', 'tsp_advance_settlement_payment_idx');
            $table->foreign('teacher_salary_advance_id', 'tsa_settlement_advance_fk')
                ->references('id')
                ->on('teacher_salary_advances')
                ->cascadeOnDelete();
            $table->foreign('teacher_salary_payment_id', 'tsa_settlement_payment_fk')
                ->references('id')
                ->on('teacher_salary_payments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_salary_advance_settlements');
        Schema::dropIfExists('teacher_salary_advances');
    }
};
