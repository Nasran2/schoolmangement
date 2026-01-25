<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('extra_class_teacher_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('extra_class_id');
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('extra_class_id')->references('id')->on('extra_classes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_class_teacher_payments');
    }
};
