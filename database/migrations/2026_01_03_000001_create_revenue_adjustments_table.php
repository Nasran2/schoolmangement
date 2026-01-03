<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('revenue_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revenue_id')->constrained('revenues')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->enum('type', ['refund', 'waiver']);
            $table->decimal('amount', 12, 2);
            $table->text('reason')->nullable();

            // Used for month-based reports (defaults to revenue.paid_at month)
            $table->unsignedSmallInteger('effective_month')->nullable(); // 1-12
            $table->unsignedSmallInteger('effective_year')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'effective_year', 'effective_month']);
            $table->index(['student_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_adjustments');
    }
};
