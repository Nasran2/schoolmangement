<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_promotion_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('from_class_room_id')->nullable()->constrained('class_rooms')->nullOnDelete();
            $table->foreignId('to_class_room_id')->nullable()->constrained('class_rooms')->nullOnDelete();
            $table->string('action', 20); // promote | demote
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_promotion_histories');
    }
};
