<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seminar_teacher_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seminar_id');
            $table->unsignedBigInteger('expense_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('seminar_id')->references('id')->on('seminars')->cascadeOnDelete();
            $table->foreign('expense_id')->references('id')->on('expenses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_teacher_payments');
    }
};
