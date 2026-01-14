<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('extra_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('payment_type', ['monthly', 'daily'])->default('daily');
            $table->decimal('fee', 10, 2)->default(0);
            $table->unsignedBigInteger('class_room_id')->nullable();
            $table->unsignedBigInteger('visiting_teacher_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('class_room_id')->references('id')->on('class_rooms')->nullOnDelete();
        });

        Schema::create('extra_class_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('extra_class_id');
            $table->unsignedBigInteger('student_id');
            $table->boolean('paid')->default(false);
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['extra_class_id', 'student_id']);
            $table->foreign('extra_class_id')->references('id')->on('extra_classes')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_class_students');
        Schema::dropIfExists('extra_classes');
    }
};
