<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure InnoDB storage engine on existing tables to avoid MySQL Error 1824
        foreach (['revenues', 'class_rooms', 'revenue_categories', 'students', 'users'] as $table) {
            if (Schema::hasTable($table)) {
                try {
                    DB::statement("ALTER TABLE `{$table}` ENGINE = InnoDB");
                } catch (\Throwable $e) {
                    // Ignore if permission not granted
                }
            }
        }

        // 2. Add class_fee_type column to revenue_categories if not present
        if (! Schema::hasColumn('revenue_categories', 'class_fee_type')) {
            Schema::table('revenue_categories', function (Blueprint $table) {
                $table->string('class_fee_type', 20)->nullable()->unique();
            });
        }
        foreach (['exam' => 'exam fee', 'facility' => 'facility fee'] as $type => $name) {
            $categoryId = DB::table('revenue_categories')->whereRaw('LOWER(name) = ?', [$name])->orderBy('id')->value('id');
            if ($categoryId) {
                DB::table('revenue_categories')->where('id', $categoryId)->update(['class_fee_type' => $type]);
            }
        }

        // 3. Create class_fee_schedules table with explicit InnoDB engine
        if (! Schema::hasTable('class_fee_schedules')) {
            Schema::create('class_fee_schedules', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('class_room_id')->constrained('class_rooms')->cascadeOnDelete();
                $table->foreignId('revenue_category_id')->constrained('revenue_categories')->restrictOnDelete();
                $table->string('fee_type', 20);
                $table->decimal('amount', 12, 2);
                $table->date('first_due_date');
                $table->date('ends_on')->nullable();
                $table->timestamps();
                $table->index(['class_room_id', 'fee_type']);
            });
        } else {
            try {
                DB::statement("ALTER TABLE `class_fee_schedules` ENGINE = InnoDB");
            } catch (\Throwable $e) {}
        }

        // 4. Add columns to revenues table (separated from foreign key constraint)
        Schema::table('revenues', function (Blueprint $table) {
            if (! Schema::hasColumn('revenues', 'class_fee_schedule_id')) {
                $table->unsignedBigInteger('class_fee_schedule_id')->nullable()->index();
            }
            if (! Schema::hasColumn('revenues', 'class_fee_due_date')) {
                $table->date('class_fee_due_date')->nullable();
            }
            if (! Schema::hasColumn('revenues', 'class_fee_submission_id')) {
                $table->uuid('class_fee_submission_id')->nullable()->unique();
            }
        });

        // 5. Add composite index on revenues
        try {
            Schema::table('revenues', function (Blueprint $table) {
                $table->index(['class_fee_schedule_id', 'student_id', 'class_fee_due_date'], 'revenues_class_fee_cycle_idx');
            });
        } catch (\Throwable $e) {}

        // 6. Safely attach foreign key constraint if supported
        try {
            Schema::table('revenues', function (Blueprint $table) {
                $table->foreign('class_fee_schedule_id', 'revenues_class_fee_schedule_id_foreign')
                    ->references('id')
                    ->on('class_fee_schedules')
                    ->restrictOnDelete();
            });
        } catch (\Throwable $e) {
            Log::warning('Could not attach foreign key revenues_class_fee_schedule_id_foreign: ' . $e->getMessage());
        }

        // 7. Permissions setup
        foreach (['class_fees.view', 'dashboard.widget.exam_fees.view', 'dashboard.widget.facility_fees.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('revenues')) {
            Schema::table('revenues', function (Blueprint $table) {
                try {
                    $table->dropIndex('revenues_class_fee_cycle_idx');
                } catch (\Throwable $e) {}
                try {
                    $table->dropForeign('revenues_class_fee_schedule_id_foreign');
                } catch (\Throwable $e) {}
                if (Schema::hasColumn('revenues', 'class_fee_schedule_id')) {
                    $table->dropColumn('class_fee_schedule_id');
                }
                if (Schema::hasColumn('revenues', 'class_fee_due_date')) {
                    $table->dropColumn('class_fee_due_date');
                }
                if (Schema::hasColumn('revenues', 'class_fee_submission_id')) {
                    $table->dropColumn('class_fee_submission_id');
                }
            });
        }
        Schema::dropIfExists('class_fee_schedules');
        if (Schema::hasColumn('revenue_categories', 'class_fee_type')) {
            Schema::table('revenue_categories', fn (Blueprint $table) => $table->dropColumn('class_fee_type'));
        }
    }
};
