<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_fee_schedules') && ! Schema::hasColumn('class_fee_schedules', 'terms')) {
            Schema::table('class_fee_schedules', function (Blueprint $table) {
                $table->json('terms')->nullable();
            });
        }

        // Historical payments are deliberately separate from income. Financial
        // reports and cash balances continue to use only the revenues table.
        if (! Schema::hasTable('class_fee_payment_histories')) {
            Schema::create('class_fee_payment_histories', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('student_id')->constrained()->restrictOnDelete();
                $table->foreignId('class_fee_schedule_id')->constrained('class_fee_schedules')->restrictOnDelete();
                $table->date('class_fee_due_date');
                $table->uuid('class_fee_submission_id')->unique();
                $table->decimal('amount', 12, 2);
                $table->date('paid_at');
                $table->string('payment_method', 30)->default('cash');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
                $table->index(['class_fee_schedule_id', 'student_id', 'class_fee_due_date'], 'fee_history_cycle_idx');
            });
        }

        if (! Schema::hasTable('class_fee_batches')) {
            Schema::create('class_fee_batches', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->uuid('id')->primary();
                $table->char('payload_hash', 64);
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->unsignedInteger('payment_count');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('class_fee_batches');
        Schema::dropIfExists('class_fee_payment_histories');
        if (Schema::hasTable('class_fee_schedules') && Schema::hasColumn('class_fee_schedules', 'terms')) {
            Schema::table('class_fee_schedules', fn (Blueprint $table) => $table->dropColumn('terms'));
        }
    }
};
