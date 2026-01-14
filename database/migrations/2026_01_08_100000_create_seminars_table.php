<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seminars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('fee_per_student', 10, 2)->default(0);
            $table->decimal('teacher_payment', 10, 2)->default(0);
            $table->unsignedBigInteger('class_room_id')->nullable(); // primary classroom (optional)
            $table->unsignedBigInteger('visiting_teacher_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('class_room_id')->references('id')->on('class_rooms')->nullOnDelete();
        });

        Schema::create('seminar_class_room', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seminar_id');
            $table->unsignedBigInteger('class_room_id');
            $table->timestamps();

            $table->unique(['seminar_id', 'class_room_id']);
            $table->foreign('seminar_id')->references('id')->on('seminars')->cascadeOnDelete();
            $table->foreign('class_room_id')->references('id')->on('class_rooms')->cascadeOnDelete();
        });

        Schema::create('seminar_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seminar_id');
            $table->unsignedBigInteger('student_id');
            $table->boolean('present')->default(false);
            $table->boolean('paid')->default(false);
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['seminar_id', 'student_id']);
            $table->foreign('seminar_id')->references('id')->on('seminars')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });

        Schema::create('seminar_teacher_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seminar_id');
            $table->unsignedBigInteger('visiting_teacher_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('seminar_id')->references('id')->on('seminars')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_teacher_payouts');
        Schema::dropIfExists('seminar_students');
        Schema::dropIfExists('seminar_class_room');
        Schema::dropIfExists('seminars');
    }
};
