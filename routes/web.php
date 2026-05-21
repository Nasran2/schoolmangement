<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Rbac\RoleController;
use App\Http\Controllers\RevenueCategoryController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\RevenueAdjustmentController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\Settings\BackupSettingsController;
use App\Http\Controllers\Settings\GeneralSettingsController;
use App\Http\Controllers\Settings\SalaryComponentSettingsController;
use App\Http\Controllers\Developer\DeveloperDashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherSalaryPaymentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OnlyAdminController;
use App\Http\Middleware\RedirectDeveloperFromDashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// Route::get('/', function () {
//     return view('welcome');
// });


// Cache clear endpoint (no permission required)
Route::get('/cache', function () {
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    
    return response()->json([
        'status' => 'success',
        'message' => 'All caches cleared successfully',
        'timestamp' => now()->toDateTimeString(),
    ]);
})->name('cache.clear');

// Storage symlink endpoint (no permission required)
Route::get('/linkstore', function () {
    $target = storage_path('app/public');
    $link = public_path('storage');

    try {
        if (! is_dir($target)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Storage target directory not found',
                'target' => $target,
                'timestamp' => now()->toDateTimeString(),
            ], 500);
        }

        if (is_link($link) || is_dir($link)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Storage link already exists',
                'link' => $link,
                'target' => $target,
                'timestamp' => now()->toDateTimeString(),
            ]);
        }

        $linked = false;
        if (function_exists('symlink')) {
            $linked = @symlink($target, $link);
        }

        // Shared hosting fallback: if symlink is not allowed, copy files.
        if (! $linked) {
            $copyRecursive = function (string $from, string $to) use (&$copyRecursive): void {
                if (! is_dir($to)) {
                    mkdir($to, 0755, true);
                }

                $items = scandir($from) ?: [];
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') {
                        continue;
                    }

                    $src = $from.DIRECTORY_SEPARATOR.$item;
                    $dst = $to.DIRECTORY_SEPARATOR.$item;

                    if (is_dir($src)) {
                        $copyRecursive($src, $dst);
                    } else {
                        copy($src, $dst);
                    }
                }
            };

            $copyRecursive($target, $link);
        }

        return response()->json([
            'status' => 'success',
            'message' => $linked ? 'Storage symlink created successfully' : 'Symlink unavailable, files copied to public/storage',
            'mode' => $linked ? 'symlink' : 'copy',
            'link' => $link,
            'target' => $target,
            'timestamp' => now()->toDateTimeString(),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to create storage link',
            'error' => $e->getMessage(),
            'link' => $link,
            'target' => $target,
            'timestamp' => now()->toDateTimeString(),
        ], 500);
    }
})->name('storage.link.public');

// Secret admin link (PIN protected)
Route::prefix('onlyadmin')->name('onlyadmin.')->group(function () {
    Route::get('/', [OnlyAdminController::class, 'index'])->name('index');
    Route::post('/unlock', [OnlyAdminController::class, 'unlock'])->name('unlock');

    Route::post('/logout', [OnlyAdminController::class, 'logout'])->middleware('onlyadmin')->name('logout');
    Route::post('/system-lock', [OnlyAdminController::class, 'setSystemLock'])->middleware('onlyadmin')->name('system_lock');
    Route::post('/pin', [OnlyAdminController::class, 'updatePin'])->middleware('onlyadmin')->name('pin');
    Route::post('/cache-routes', [OnlyAdminController::class, 'cacheRoutes'])->middleware('onlyadmin')->name('cache.routes');
});

Route::get('/', function () {
    if (Auth::check() && Auth::user()?->hasRole('Developer')) {
        return redirect()->route('developer.dashboard');
    }

    return redirect()->route('dashboard');
});

// Some deployments expose the app through /public/ so treat that path as the root.
Route::get('/public', function () {
    if (Auth::check() && Auth::user()?->hasRole('Developer')) {
        return redirect()->route('developer.dashboard');
    }

    return redirect()->route('dashboard');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', RedirectDeveloperFromDashboard::class, 'permission:dashboard.view'])
    ->name('dashboard');

Route::get('/opening-balances', [\App\Http\Controllers\OpeningBalanceController::class, 'create'])
    ->middleware('auth')
    ->name('opening-balance.create');

Route::post('/opening-balances', [\App\Http\Controllers\OpeningBalanceController::class, 'store'])
    ->middleware('auth')
    ->name('opening-balance.store');

Route::delete('/opening-balances/{openingBalance}', [\App\Http\Controllers\OpeningBalanceController::class, 'destroy'])
    ->middleware('auth')
    ->name('opening-balance.destroy');

// Public storage fallback: serve /storage/* without needing symlink
Route::get('/storage/{path}', [StorageController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.show');

Route::middleware('auth')->group(function () {
    Route::post('/academic-year', AcademicYearController::class)->name('academic-year.set');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('developer')->name('developer.')->middleware('role:Developer')->group(function () {
        Route::get('/dashboard', [DeveloperDashboardController::class, 'index'])->name('dashboard');
        Route::get('/students', [DeveloperDashboardController::class, 'students'])->name('students');
        Route::get('/teachers', [DeveloperDashboardController::class, 'teachers'])->name('teachers');
        Route::get('/users', [DeveloperDashboardController::class, 'users'])->name('users');
        Route::post('/commands/run', [DeveloperDashboardController::class, 'runCommand'])->name('commands.run');
        Route::post('/maintenance/enable', [DeveloperDashboardController::class, 'enableMaintenance'])->name('maintenance.enable');
        Route::post('/maintenance/disable', [DeveloperDashboardController::class, 'disableMaintenance'])->name('maintenance.disable');
        Route::post('/upgrade', [DeveloperDashboardController::class, 'upgrade'])->name('upgrade');
        Route::post('/users/{user}/status', [DeveloperDashboardController::class, 'updateUserStatus'])->name('users.status');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/general', [GeneralSettingsController::class, 'edit'])
            ->middleware('permission:settings.manage|settings.general.manage')
            ->name('general.edit');
        Route::put('/general', [GeneralSettingsController::class, 'update'])
            ->middleware('permission:settings.manage|settings.general.manage')
            ->name('general.update');

        Route::get('/status', [\App\Http\Controllers\Settings\SystemStatusController::class, 'index'])
            ->middleware('permission:settings.manage|settings.status.view')
            ->name('status.index');

        Route::get('/sms', [\App\Http\Controllers\Settings\SmsSettingsController::class, 'edit'])
            ->middleware('permission:settings.manage|settings.sms.manage')
            ->name('sms.edit');
        Route::put('/sms', [\App\Http\Controllers\Settings\SmsSettingsController::class, 'update'])
            ->middleware('permission:settings.manage|settings.sms.manage')
            ->name('sms.update');

        Route::post('/sms/test', [\App\Http\Controllers\Settings\SmsSettingsController::class, 'sendTest'])
            ->middleware('permission:settings.manage|settings.sms.manage')
            ->name('sms.test');

        Route::get('/email', [\App\Http\Controllers\Settings\EmailSettingsController::class, 'edit'])
            ->middleware('permission:settings.manage|settings.email.manage')
            ->name('email.edit');
        Route::put('/email', [\App\Http\Controllers\Settings\EmailSettingsController::class, 'update'])
            ->middleware('permission:settings.manage|settings.email.manage')
            ->name('email.update');

        Route::post('/email/test', [\App\Http\Controllers\Settings\EmailSettingsController::class, 'sendTest'])
            ->middleware('permission:settings.manage|settings.email.manage')
            ->name('email.test');
        Route::get('/printer', [\App\Http\Controllers\Settings\PrinterSettingsController::class, 'edit'])
            ->middleware('permission:settings.manage|settings.printer.manage')
            ->name('printer.edit');
        Route::put('/printer', [\App\Http\Controllers\Settings\PrinterSettingsController::class, 'update'])
            ->middleware('permission:settings.manage|settings.printer.manage')
            ->name('printer.update');
        Route::get('/promotion', [\App\Http\Controllers\Settings\PromotionSettingsController::class, 'edit'])
            ->middleware('permission:settings.manage|settings.promotion.manage')
            ->name('promotion.edit');
        Route::put('/promotion', [\App\Http\Controllers\Settings\PromotionSettingsController::class, 'update'])
            ->middleware('permission:settings.manage|settings.promotion.manage')
            ->name('promotion.update');

        Route::get('/salary-components', [SalaryComponentSettingsController::class, 'edit'])
            ->middleware('permission:settings.manage|settings.salary_components.manage')
            ->name('salary-components.edit');
        Route::put('/salary-components', [SalaryComponentSettingsController::class, 'update'])
            ->middleware('permission:settings.manage|settings.salary_components.manage')
            ->name('salary-components.update');

        Route::get('/backups', [BackupSettingsController::class, 'index'])
            ->middleware('permission:settings.manage|settings.backups.manage')
            ->name('backups.index');
        Route::put('/backups', [BackupSettingsController::class, 'updateConfig'])
            ->middleware('permission:settings.manage|settings.backups.manage')
            ->name('backups.update');
        Route::post('/backups/run', [BackupSettingsController::class, 'run'])
            ->middleware('permission:settings.manage|settings.backups.manage')
            ->name('backups.run');
        Route::get('/backups/{file}', [BackupSettingsController::class, 'download'])
            ->middleware('permission:settings.manage|settings.backups.manage')
            ->name('backups.download');
        Route::delete('/backups/{file}', [BackupSettingsController::class, 'destroy'])
            ->middleware('permission:settings.manage|settings.backups.manage')
            ->name('backups.destroy');

        Route::get('/opening-balance', [\App\Http\Controllers\Settings\OpeningBalanceSettingsController::class, 'edit'])
            ->middleware('permission:settings.manage|settings.opening_balance.manage')
            ->name('opening-balance.edit');
        Route::put('/opening-balance', [\App\Http\Controllers\Settings\OpeningBalanceSettingsController::class, 'update'])
            ->middleware('permission:settings.manage|settings.opening_balance.manage')
            ->name('opening-balance.update');

        Route::post('/opening-balance/reset', [\App\Http\Controllers\Settings\OpeningBalanceSettingsController::class, 'reset'])
            ->middleware('permission:settings.manage|settings.opening_balance.reset')
            ->name('opening-balance.reset');

    });

    Route::prefix('rbac')->name('rbac.')->middleware('permission:roles.manage')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });

    Route::prefix('users')->name('users.')->middleware('permission:users.manage')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::post('/{user}/status', [UserController::class, 'updateStatus'])->name('status');
    });

    Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');

        Route::get('/exports', [ReportController::class, 'exports'])->name('exports');
        Route::get('/download-all', [ReportController::class, 'downloadAllPdfBundle'])
            ->middleware('permission:reports.download')
            ->name('download_all');

        Route::get('/revenue', [ReportController::class, 'revenue'])
            ->middleware('permission:reports.revenue.view')
            ->name('revenue');
        Route::get('/expense', [ReportController::class, 'expense'])
            ->middleware('permission:reports.expense.view')
            ->name('expense');
        Route::get('/outflows', [ReportController::class, 'outflows'])
            ->middleware('permission:reports.outflows.view')
            ->name('outflows');
        Route::get('/financial', [ReportController::class, 'financial'])
            ->middleware('permission:reports.financial.view')
            ->name('financial');

        Route::get('/daily-ledger', [ReportController::class, 'dailyLedger'])
            ->middleware('permission:reports.daily_ledger.view')
            ->name('daily_ledger');

        Route::get('/transactions/cash', [ReportController::class, 'cashTransactions'])
            ->middleware('permission:reports.cash_transactions.view')
            ->name('cash_transactions');
        Route::get('/transactions/bank', [ReportController::class, 'bankTransactions'])
            ->middleware('permission:reports.bank_transactions.view')
            ->name('bank_transactions');

        Route::get('/cheques', [ReportController::class, 'chequeHistory'])
            ->middleware('permission:reports.cheque_history.view')
            ->name('cheque_history');

        Route::get('/teacher/epf', [ReportController::class, 'teacherEpf'])
            ->middleware('permission:reports.teacher_epf.view')
            ->name('teacher_epf');
        Route::get('/teacher/etf', [ReportController::class, 'teacherEtf'])
            ->middleware('permission:reports.teacher_etf.view')
            ->name('teacher_etf');

        Route::get('/company/epf', [ReportController::class, 'companyEpf'])
            ->middleware('permission:reports.company_epf.view')
            ->name('company_epf');

        Route::get('/teacher/epf-etf-totals', [ReportController::class, 'epfEtfTotals'])
            ->middleware('permission:reports.epf_etf_totals.view')
            ->name('epf_etf_totals');
        Route::get('/students/all', [ReportController::class, 'students'])
            ->middleware('permission:reports.view')
            ->name('students');
        Route::get('/students/due', [ReportController::class, 'studentDue'])
            ->middleware('permission:reports.student_due.view')
            ->name('student_due');

        // Fee-focused reports
        Route::get('/fees/collection-summary', [ReportController::class, 'feeCollectionSummary'])
            ->middleware('permission:reports.fee_collection_summary.view')
            ->name('fee_collection_summary');
        Route::get('/fees/collection-by-class', [ReportController::class, 'feeCollectionByClass'])
            ->middleware('permission:reports.fee_collection_by_class.view')
            ->name('fee_collection_by_class');
        Route::get('/fees/collection-by-category', [ReportController::class, 'feeCollectionByCategory'])
            ->middleware('permission:reports.fee_collection_by_category.view')
            ->name('fee_collection_by_category');
        Route::get('/fees/collection-vs-expected', [ReportController::class, 'feeCollectionVsExpected'])
            ->middleware('permission:reports.fee_collection_vs_expected.view')
            ->name('fee_collection_vs_expected');

        // Student due insights
        Route::get('/students/due-aging', [ReportController::class, 'studentDueAging'])
            ->middleware('permission:reports.student_due_aging.view')
            ->name('student_due_aging');
        Route::get('/students/top-due', [ReportController::class, 'studentTopDue'])
            ->middleware('permission:reports.student_top_due.view')
            ->name('student_top_due');

        // Placeholders (require extra tracking fields)
        Route::get('/fees/discounts', [ReportController::class, 'feeDiscounts'])
            ->middleware('permission:reports.fee_discounts.view')
            ->name('fee_discounts');
        Route::get('/fees/refunds', [ReportController::class, 'feeRefunds'])
            ->middleware('permission:reports.fee_refunds.view')
            ->name('fee_refunds');

        // Collections for seminars and extra classes
        Route::get('/seminars/collection', [ReportController::class, 'seminarsCollection'])
            ->middleware('permission:reports.seminars_collection.view')
            ->name('seminars_collection');
        Route::get('/extra-classes/collection', [ReportController::class, 'extraClassesCollection'])
            ->middleware('permission:reports.extra_classes_collection.view')
            ->name('extra_classes_collection');
    });

    // SMS sending endpoints
    Route::post('/sms/student/{student}', [\App\Http\Controllers\SmsController::class, 'sendStudent'])
        ->middleware('permission:sms.send.individual')
        ->name('sms.student');
    Route::post('/sms/students/due', [\App\Http\Controllers\SmsController::class, 'sendDueStudents'])
        ->middleware('permission:sms.send.bulk')
        ->name('sms.students.due');
    Route::post('/sms/classes/selected', [\App\Http\Controllers\SmsController::class, 'sendSelectedClasses'])
        ->middleware('permission:sms.send.selected_grades')
        ->name('sms.classes.selected');

    Route::prefix('revenue')->name('revenue.')->group(function () {
        Route::resource('categories', RevenueCategoryController::class)
            ->middleware('permission:revenue.categories.manage');

        Route::get('categories/{category}/classes/{classRoom}', [\App\Http\Controllers\RevenueCategoryCollectionController::class, 'class'])
            ->name('categories.classes.show')
            ->middleware('permission:revenue.categories.manage');

        Route::post('categories/{category}/classes/{classRoom}/bulk-pay', [\App\Http\Controllers\RevenueCategoryCollectionController::class, 'bulkStore'])
            ->name('categories.classes.bulkPay')
            ->middleware('permission:revenue.manage');

        Route::get('reminders', [\App\Http\Controllers\RevenueReminderController::class, 'index'])
            ->name('reminders.index')
            ->middleware('permission:revenue.manage');
        Route::get('adjustments', [RevenueAdjustmentController::class, 'index'])
            ->middleware('permission:revenue.manage')
            ->name('adjustments.index');

        Route::get('items', [RevenueController::class, 'index'])
            ->middleware('permission:revenue.manage')
            ->name('items.index');
        Route::get('items/create', [RevenueController::class, 'create'])
            ->middleware('permission:revenue.add')
            ->name('items.create');
                Route::get('items/{item}/receipt', [RevenueController::class, 'receipt'])
                    ->middleware('permission:revenue.manage')
                    ->name('items.receipt');
        Route::post('items', [RevenueController::class, 'store'])
            ->middleware('permission:revenue.add')
            ->name('items.store');
        Route::post('items/{item}/refund', [RevenueAdjustmentController::class, 'refund'])
            ->middleware('permission:revenue.manage')
            ->name('items.refund');
        Route::post('items/{item}/waiver', [RevenueAdjustmentController::class, 'waiver'])
            ->middleware('permission:revenue.manage')
            ->name('items.waiver');

        // Cheque confirmation workflow
        Route::post('items/{item}/cheque/passed', [RevenueController::class, 'markChequePassed'])
            ->middleware('permission:revenue.manage')
            ->name('items.cheque.passed');
        Route::post('items/{item}/cheque/returned', [RevenueController::class, 'markChequeReturned'])
            ->middleware('permission:revenue.manage')
            ->name('items.cheque.returned');
        // Preview allocation for monthly payments
        Route::post('items/preview-allocation', [RevenueController::class, 'previewAllocation'])
            ->middleware('permission:revenue.add')
            ->name('items.preview_allocation');
        Route::get('items/{item}/edit', [RevenueController::class, 'edit'])
            ->middleware('permission:revenue.manage')
            ->name('items.edit');
        Route::put('items/{item}', [RevenueController::class, 'update'])
            ->middleware('permission:revenue.manage')
            ->name('items.update');
        Route::delete('items/{item}', [RevenueController::class, 'destroy'])
            ->middleware('permission:revenue.delete')
            ->name('items.destroy');

        Route::get('cheques', [RevenueController::class, 'chequesIndex'])
            ->middleware('permission:revenue.manage')
            ->name('cheques.index');
    });

    // Printer slip routes
    Route::get('/printer/revenue/{item}', [\App\Http\Controllers\PrinterSlipController::class, 'revenue'])
        ->middleware('permission:revenue.manage')
        ->name('printer.revenue');
    Route::get('/printer/refund/{adjustment}', [\App\Http\Controllers\PrinterSlipController::class, 'refund'])
        ->middleware('permission:revenue.manage')
        ->name('printer.refund');
    Route::get('/printer/teacher-salary/{payment}', [\App\Http\Controllers\PrinterSlipController::class, 'teacher'])
        ->middleware('permission:teachers.salary.pay')
        ->name('printer.teacher');

    Route::prefix('expense')->name('expense.')->group(function () {
        Route::resource('categories', ExpenseCategoryController::class)
            ->middleware('permission:expense.categories.manage');

        Route::get('items', [ExpenseController::class, 'index'])
            ->middleware('permission:expense.manage')
            ->name('items.index');
        Route::get('items/create', [ExpenseController::class, 'create'])
            ->middleware('permission:expense.add')
            ->name('items.create');
        Route::post('items', [ExpenseController::class, 'store'])
            ->middleware('permission:expense.add')
            ->name('items.store');
        Route::get('items/{item}/edit', [ExpenseController::class, 'edit'])
            ->middleware('permission:expense.manage')
            ->name('items.edit');
        Route::put('items/{item}', [ExpenseController::class, 'update'])
            ->middleware('permission:expense.manage')
            ->name('items.update');
        Route::delete('items/{item}', [ExpenseController::class, 'destroy'])
            ->middleware('permission:expense.delete')
            ->name('items.destroy');
    });

    Route::get('students', [StudentController::class, 'index'])->middleware('permission:students.manage')->name('students.index');
    Route::get('students/alumni', [StudentController::class, 'alumni'])
        ->middleware('permission:students.manage')
        ->name('students.alumni');
    Route::post('students/alumni/leaving-docs', [StudentController::class, 'alumniBulkLeavingDocs'])
        ->middleware('permission:students.manage')
        ->name('students.alumni.leaving_docs');
        // Lightweight student search for selectors (admission number, name, phone) + optional class filter
        Route::get('students/search', [StudentController::class, 'search'])
            ->middleware('permission:revenue.add')
            ->name('students.search');
        Route::post('students/promote', [\App\Http\Controllers\PromotionController::class, 'promote'])
            ->middleware('permission:students.promote')
            ->name('students.promote');
        Route::post('students/demote', [\App\Http\Controllers\PromotionController::class, 'demote'])
            ->middleware('permission:students.demote')
            ->name('students.demote');
        // Per-student promotion/demotion
        Route::post('students/{student}/promote', [\App\Http\Controllers\PromotionController::class, 'promoteStudent'])
            ->middleware('permission:students.promote')
            ->name('students.promote.one');
        Route::post('students/{student}/demote', [\App\Http\Controllers\PromotionController::class, 'demoteStudent'])
            ->middleware('permission:students.demote')
            ->name('students.demote.one');

        // Current-month fee selection after promote/demote
        Route::post('students/{student}/monthly-fee/current', [StudentController::class, 'setCurrentMonthFee'])
            ->middleware('permission:revenue.add')
            ->name('students.monthly_fee.current');
        Route::patch('students/{student}/fee-start-date', [StudentController::class, 'updateFeeStartDate'])
            ->middleware('permission:students.manage')
            ->name('students.fee_start_date.update');
        Route::post('students/{student}/monthly-fee-credits', [StudentController::class, 'storeMonthlyFeeCredit'])
            ->middleware('permission:students.manage')
            ->name('students.monthly_fee.credits.store');
        Route::delete('students/{student}/monthly-fee-credits/{credit}', [StudentController::class, 'deleteMonthlyFeeCredit'])
            ->middleware('permission:students.manage')
            ->name('students.monthly_fee.credits.delete');
        Route::get('students/check-admission', [StudentController::class, 'checkAdmissionNumber'])
            ->middleware('permission:students.add')
            ->name('students.check_admission');
    Route::get('students/create', [StudentController::class, 'create'])->middleware('permission:students.add')->name('students.create');
    Route::post('students', [StudentController::class, 'store'])->middleware('permission:students.add')->name('students.store');
    Route::get('students/{student}', [StudentController::class, 'show'])->middleware('permission:students.manage')->name('students.show');
    Route::post('students/{student}/leaving-docs', [StudentController::class, 'updateLeavingDocs'])
        ->middleware('permission:students.manage')
        ->name('students.leaving_docs');
    Route::post('students/{student}/mark-alumni', [StudentController::class, 'markAsAlumni'])
        ->middleware('permission:students.manage')
        ->name('students.mark_alumni');
    Route::post('students/{student}/readmit', [StudentController::class, 'reAdmit'])
        ->middleware('permission:students.manage')
        ->name('students.readmit');
    Route::get('students/{student}/statement', [StudentController::class, 'statement'])->middleware('permission:students.manage')->name('students.statement');
    Route::get('students/{student}/admission', [StudentController::class, 'admission'])->middleware('permission:students.manage')->name('students.admission');
    Route::get('students/{student}/edit', [StudentController::class, 'edit'])->middleware('permission:students.manage')->name('students.edit');
    Route::put('students/{student}', [StudentController::class, 'update'])->middleware('permission:students.manage')->name('students.update');
    Route::delete('students/{student}', [StudentController::class, 'destroy'])->middleware('permission:students.delete')->name('students.destroy');

    Route::get('students/bulk/upload', [\App\Http\Controllers\StudentsBulkUploadController::class, 'create'])
        ->middleware('permission:students.bulk_upload')
        ->name('students.bulk.create');
    Route::get('students/bulk/template', [\App\Http\Controllers\StudentsBulkUploadController::class, 'downloadTemplate'])
        ->middleware('permission:students.bulk_upload')
        ->name('students.bulk.template');
    Route::post('students/bulk/upload', [\App\Http\Controllers\StudentsBulkUploadController::class, 'store'])
        ->middleware('permission:students.bulk_upload')
        ->name('students.bulk.store');

    Route::get('teachers', [TeacherController::class, 'index'])->middleware('permission:teachers.manage')->name('teachers.index');
    Route::get('teachers/create', [TeacherController::class, 'create'])->middleware('permission:teachers.add')->name('teachers.create');
    Route::post('teachers', [TeacherController::class, 'store'])->middleware('permission:teachers.add')->name('teachers.store');
    Route::get('teachers/search', [TeacherController::class, 'search'])
        ->middleware('permission:teachers.salary.pay')
        ->name('teachers.search');
    Route::get('teachers/{teacher}', [TeacherController::class, 'show'])->middleware('permission:teachers.manage')->name('teachers.show');
    Route::get('teachers/{teacher}/edit', [TeacherController::class, 'edit'])->middleware('permission:teachers.manage')->name('teachers.edit');
    Route::put('teachers/{teacher}', [TeacherController::class, 'update'])->middleware('permission:teachers.manage')->name('teachers.update');
    Route::post('teachers/{teacher}/salary', [TeacherController::class, 'updateSalary'])
        ->middleware('permission:teachers.manage|teachers.salary.components')
        ->name('teachers.salary.update');
    Route::delete('teachers/{teacher}', [TeacherController::class, 'destroy'])->middleware('permission:teachers.delete')->name('teachers.destroy');

    Route::get('teachers/bulk/upload', [\App\Http\Controllers\TeachersBulkUploadController::class, 'create'])
        ->middleware('permission:teachers.bulk_upload')
        ->name('teachers.bulk.create');
    Route::post('teachers/bulk/upload', [\App\Http\Controllers\TeachersBulkUploadController::class, 'store'])
        ->middleware('permission:teachers.bulk_upload')
        ->name('teachers.bulk.store');

    Route::get('teacher-salary-payments/summary', [TeacherSalaryPaymentController::class, 'summary'])
        ->middleware('permission:teachers.salary.pay|teachers.salary.summary.view')
        ->name('teacher-salary-payments.summary');

    Route::resource('teacher-salary-payments', TeacherSalaryPaymentController::class)
        ->middleware('permission:teachers.salary.pay')
        ->whereNumber('teacher_salary_payment');
    
    Route::get('teacher-salary-payments/{teacherSalaryPayment}/receipt', [TeacherSalaryPaymentController::class, 'receipt'])
        ->middleware('permission:teachers.salary.pay')
        ->name('teacher-salary-payments.receipt');
    
    Route::get('teacher-salary-payments/{teacherSalaryPayment}/payslip', [TeacherSalaryPaymentController::class, 'payslip'])
        ->middleware('permission:teachers.salary.pay')
        ->name('teacher-salary-payments.payslip');

    Route::post('teacher-salary-payments/{teacherSalaryPayment}/email-payslip', [TeacherSalaryPaymentController::class, 'emailPayslip'])
        ->middleware('permission:teachers.salary.pay')
        ->name('teacher-salary-payments.email-payslip');

    Route::prefix('classrooms')->name('classrooms.')->group(function () {
        Route::get('/', [ClassRoomController::class, 'index'])
            ->middleware('permission:classrooms.view')
            ->name('index');
        Route::get('/create', [ClassRoomController::class, 'create'])
            ->middleware('permission:classrooms.create')
            ->name('create');
        Route::post('/', [ClassRoomController::class, 'store'])
            ->middleware('permission:classrooms.create')
            ->name('store');
        Route::get('/{classroom}', [ClassRoomController::class, 'show'])
            ->middleware('permission:classrooms.view')
            ->name('show');
        Route::get('/{classroom}/edit', [ClassRoomController::class, 'edit'])
            ->middleware('permission:classrooms.update')
            ->name('edit');
        Route::put('/{classroom}', [ClassRoomController::class, 'update'])
            ->middleware('permission:classrooms.update')
            ->name('update');
        Route::delete('/{classroom}', [ClassRoomController::class, 'destroy'])
            ->middleware('permission:classrooms.delete')
            ->name('destroy');
    });

    // Seminars module
    Route::prefix('seminars')->name('seminars.')->middleware('role:Super Admin|Admin|Developer')->group(function () {
        Route::get('/', [\App\Http\Controllers\SeminarController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\SeminarController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\SeminarController::class, 'store'])->name('store');
        Route::get('/{seminar}', [\App\Http\Controllers\SeminarController::class, 'show'])->name('show');
        Route::get('/{seminar}/edit', [\App\Http\Controllers\SeminarController::class, 'edit'])->name('edit');
        Route::put('/{seminar}', [\App\Http\Controllers\SeminarController::class, 'update'])->name('update');
        Route::delete('/{seminar}', [\App\Http\Controllers\SeminarController::class, 'destroy'])->name('destroy');

        // Payments & attendance
        Route::get('/{seminar}/payments', [\App\Http\Controllers\SeminarPaymentController::class, 'index'])->name('payments');
        Route::post('/{seminar}/payments', [\App\Http\Controllers\SeminarPaymentController::class, 'updateAttendancePayment'])->name('payments.update');

        Route::post('/{seminar}/teacher-payments', [\App\Http\Controllers\SeminarController::class, 'storeTeacherPayment'])->name('teacher-payments.store');
        Route::delete('/{seminar}/teacher-payments/{payment}', [\App\Http\Controllers\SeminarController::class, 'destroyTeacherPayment'])->name('teacher-payments.destroy');

        // Reports
        Route::get('/reports/due', [\App\Http\Controllers\SeminarReportController::class, 'due'])->name('reports.due');
    });

    // Teacher lookup (Teachers + Visiting Teachers)
    Route::get('teacher-lookup', \App\Http\Controllers\TeacherLookupController::class)
        ->middleware('role:Super Admin|Admin|Developer')
        ->name('teacher-lookup');

    // Extra classes module
    Route::prefix('extra-classes')->name('extra-classes.')->middleware('role:Super Admin|Admin|Developer')->group(function () {
        Route::get('/', [\App\Http\Controllers\ExtraClassController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\ExtraClassController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\ExtraClassController::class, 'store'])->name('store');
        Route::get('/{extraClass}', [\App\Http\Controllers\ExtraClassController::class, 'show'])->name('show');
        Route::get('/{extraClass}/edit', [\App\Http\Controllers\ExtraClassController::class, 'edit'])->name('edit');
        Route::put('/{extraClass}', [\App\Http\Controllers\ExtraClassController::class, 'update'])->name('update');
        Route::delete('/{extraClass}', [\App\Http\Controllers\ExtraClassController::class, 'destroy'])->name('destroy');
        // Payments
        Route::get('/{extraClass}/payments', [\App\Http\Controllers\ExtraClassController::class, 'payments'])->name('payments');
        Route::post('/{extraClass}/payments', [\App\Http\Controllers\ExtraClassController::class, 'updatePayments'])->name('payments.update');
        Route::post('/{extraClass}/payments/{enrollment}/toggle', [\App\Http\Controllers\ExtraClassController::class, 'togglePayment'])->name('payments.toggle');
        Route::delete('/{extraClass}/enrollments/{enrollment}', [\App\Http\Controllers\ExtraClassController::class, 'removeEnrollment'])->name('enrollments.destroy');
        Route::post('/{extraClass}/pay-daily', [\App\Http\Controllers\ExtraClassController::class, 'payDaily'])->name('pay-daily');
        Route::post('/{extraClass}/teacher-payments', [\App\Http\Controllers\ExtraClassController::class, 'storeTeacherPayment'])->name('teacher-payments.store');
        Route::delete('/{extraClass}/teacher-payments/{payment}', [\App\Http\Controllers\ExtraClassController::class, 'destroyTeacherPayment'])->name('teacher-payments.destroy');
    });

    // Visiting teachers
    Route::prefix('visiting-teachers')->name('visiting-teachers.')->middleware('role:Super Admin|Admin|Developer')->group(function () {
        Route::get('/', [\App\Http\Controllers\VisitingTeacherController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\VisitingTeacherController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\VisitingTeacherController::class, 'store'])->name('store');
        Route::get('/{visitingTeacher}', [\App\Http\Controllers\VisitingTeacherController::class, 'show'])->name('show');
        Route::get('/{visitingTeacher}/edit', [\App\Http\Controllers\VisitingTeacherController::class, 'edit'])->name('edit');
        Route::put('/{visitingTeacher}', [\App\Http\Controllers\VisitingTeacherController::class, 'update'])->name('update');
        Route::delete('/{visitingTeacher}', [\App\Http\Controllers\VisitingTeacherController::class, 'destroy'])->name('destroy');
    });

    // Activity / Audit Logs
    Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])
        ->middleware('permission:audit_logs.view')
        ->name('audit_logs.index');
});

require __DIR__.'/auth.php';
