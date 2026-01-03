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
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherSalaryPaymentController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', DashboardController::class)->middleware(['auth','permission:dashboard.view'])->name('dashboard');

// Public storage fallback: serve /storage/* without needing symlink
Route::get('/storage/{path}', [StorageController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.show');

Route::middleware('auth')->group(function () {
    Route::post('/academic-year', AcademicYearController::class)->name('academic-year.set');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/general', [GeneralSettingsController::class, 'edit'])
            ->middleware('permission:settings.manage')
            ->name('general.edit');
        Route::put('/general', [GeneralSettingsController::class, 'update'])
            ->middleware('permission:settings.manage')
            ->name('general.update');

        Route::get('/status', [\App\Http\Controllers\Settings\SystemStatusController::class, 'index'])
            ->middleware('permission:settings.manage')
            ->name('status.index');

        Route::get('/sms', [\App\Http\Controllers\Settings\SmsSettingsController::class, 'edit'])
            ->middleware('permission:settings.manage')
            ->name('sms.edit');
        Route::put('/sms', [\App\Http\Controllers\Settings\SmsSettingsController::class, 'update'])
            ->middleware('permission:settings.manage')
            ->name('sms.update');

        Route::post('/sms/test', [\App\Http\Controllers\Settings\SmsSettingsController::class, 'sendTest'])
            ->middleware('permission:settings.manage')
            ->name('sms.test');

        Route::get('/email', [\App\Http\Controllers\Settings\EmailSettingsController::class, 'edit'])
            ->middleware('permission:settings.manage')
            ->name('email.edit');
        Route::put('/email', [\App\Http\Controllers\Settings\EmailSettingsController::class, 'update'])
            ->middleware('permission:settings.manage')
            ->name('email.update');

        Route::post('/email/test', [\App\Http\Controllers\Settings\EmailSettingsController::class, 'sendTest'])
            ->middleware('permission:settings.manage')
            ->name('email.test');
        Route::get('/printer', [\App\Http\Controllers\Settings\PrinterSettingsController::class, 'edit'])
            ->middleware('permission:settings.manage')
            ->name('printer.edit');
        Route::put('/printer', [\App\Http\Controllers\Settings\PrinterSettingsController::class, 'update'])
            ->middleware('permission:settings.manage')
            ->name('printer.update');
        Route::get('/promotion', [\App\Http\Controllers\Settings\PromotionSettingsController::class, 'edit'])
            ->middleware('permission:settings.manage')
            ->name('promotion.edit');
        Route::put('/promotion', [\App\Http\Controllers\Settings\PromotionSettingsController::class, 'update'])
            ->middleware('permission:settings.manage')
            ->name('promotion.update');

        Route::get('/salary-components', [SalaryComponentSettingsController::class, 'edit'])
            ->middleware('permission:settings.manage')
            ->name('salary-components.edit');
        Route::put('/salary-components', [SalaryComponentSettingsController::class, 'update'])
            ->middleware('permission:settings.manage')
            ->name('salary-components.update');

        Route::get('/backups', [BackupSettingsController::class, 'index'])
            ->middleware('permission:settings.manage')
            ->name('backups.index');
        Route::put('/backups', [BackupSettingsController::class, 'updateConfig'])
            ->middleware('permission:settings.manage')
            ->name('backups.update');
        Route::post('/backups/run', [BackupSettingsController::class, 'run'])
            ->middleware('permission:settings.manage')
            ->name('backups.run');
        Route::get('/backups/{file}', [BackupSettingsController::class, 'download'])
            ->middleware('permission:settings.manage')
            ->name('backups.download');
        Route::delete('/backups/{file}', [BackupSettingsController::class, 'destroy'])
            ->middleware('permission:settings.manage')
            ->name('backups.destroy');
    });

    Route::prefix('rbac')->name('rbac.')->middleware('permission:roles.manage')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });

    Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');

        Route::get('/revenue', [ReportController::class, 'revenue'])
            ->middleware('permission:reports.revenue.view')
            ->name('revenue');
        Route::get('/expense', [ReportController::class, 'expense'])
            ->middleware('permission:reports.expense.view')
            ->name('expense');
        Route::get('/financial', [ReportController::class, 'financial'])
            ->middleware('permission:reports.financial.view')
            ->name('financial');

        Route::get('/teacher/epf', [ReportController::class, 'teacherEpf'])
            ->middleware('permission:reports.teacher_epf.view')
            ->name('teacher_epf');
        Route::get('/teacher/etf', [ReportController::class, 'teacherEtf'])
            ->middleware('permission:reports.teacher_etf.view')
            ->name('teacher_etf');
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
    Route::get('students/create', [StudentController::class, 'create'])->middleware('permission:students.add')->name('students.create');
    Route::post('students', [StudentController::class, 'store'])->middleware('permission:students.add')->name('students.store');
    Route::get('students/{student}', [StudentController::class, 'show'])->middleware('permission:students.manage')->name('students.show');
    Route::get('students/{student}/statement', [StudentController::class, 'statement'])->middleware('permission:students.manage')->name('students.statement');
    Route::get('students/{student}/admission', [StudentController::class, 'admission'])->middleware('permission:students.manage')->name('students.admission');
    Route::get('students/{student}/edit', [StudentController::class, 'edit'])->middleware('permission:students.manage')->name('students.edit');
    Route::put('students/{student}', [StudentController::class, 'update'])->middleware('permission:students.manage')->name('students.update');
    Route::delete('students/{student}', [StudentController::class, 'destroy'])->middleware('permission:students.delete')->name('students.destroy');

    Route::get('students/bulk/upload', [\App\Http\Controllers\StudentsBulkUploadController::class, 'create'])
        ->middleware('permission:students.bulk_upload')
        ->name('students.bulk.create');
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
    Route::delete('teachers/{teacher}', [TeacherController::class, 'destroy'])->middleware('permission:teachers.delete')->name('teachers.destroy');

    Route::get('teachers/bulk/upload', [\App\Http\Controllers\TeachersBulkUploadController::class, 'create'])
        ->middleware('permission:teachers.bulk_upload')
        ->name('teachers.bulk.create');
    Route::post('teachers/bulk/upload', [\App\Http\Controllers\TeachersBulkUploadController::class, 'store'])
        ->middleware('permission:teachers.bulk_upload')
        ->name('teachers.bulk.store');

    Route::resource('teacher-salary-payments', TeacherSalaryPaymentController::class)
        ->middleware('permission:teachers.salary.pay');
    
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
});

require __DIR__.'/auth.php';
