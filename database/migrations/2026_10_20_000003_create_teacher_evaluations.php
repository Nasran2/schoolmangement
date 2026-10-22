<?php

use App\Services\TeacherEvaluations\EvaluationForm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_evaluations')) {
            Schema::create('teacher_evaluations', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->string('teacher_name');
            $table->string('staff_id', 80)->nullable();
            $table->string('type', 10);
            $table->unsignedSmallInteger('appraisal_year')->nullable();
            $table->string('section', 50)->nullable();
            $table->string('subject_class')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('years_of_service', 80)->nullable();
            $table->string('evaluator')->nullable();
            $table->date('review_date')->nullable();
            $table->string('event_name')->nullable();
            $table->date('event_date')->nullable();
            $table->text('responsibility')->nullable();
            $table->json('criteria')->nullable();
            $table->json('evidence')->nullable();
            $table->json('development')->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->text('teacher_comments')->nullable();
            $table->text('management_comments')->nullable();
            $table->string('teacher_signed_name')->nullable();
            $table->date('teacher_signed_date')->nullable();
            $table->string('evaluator_signed_name')->nullable();
            $table->date('evaluator_signed_date')->nullable();
            $table->date('follow_up_completed_date')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->string('status', 15)->default('draft');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->unique(['teacher_id', 'appraisal_year']);
            $table->index(['type', 'status', 'review_date']);
            $table->index('event_date');
        });
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ([...EvaluationForm::HR_PERMISSIONS, 'teacher_evaluations.delete'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        Role::findOrCreate('HR admin', 'web')->syncPermissions(EvaluationForm::HR_PERMISSIONS);
        foreach (['Admin', 'Super Admin'] as $name) {
            Role::findOrCreate($name, 'web')->givePermissionTo([...EvaluationForm::HR_PERMISSIONS, 'teacher_evaluations.delete']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_evaluations');
    }
};
