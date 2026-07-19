<?php
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\RevenueCategory;
use App\Models\ExpenseCategory;
use App\Models\Revenue;
use App\Models\Expense;
use App\Models\TeacherSalaryPayment;
use App\Models\StudentMonthFeeAllocation;
use Carbon\Carbon;

DB::statement('SET FOREIGN_KEY_CHECKS=0;');

$tablesToTruncate = [
    'class_rooms', 'expense_categories', 'expenses', 'revenue_categories', 'revenues', 
    'student_month_fee_allocations', 'students', 'teacher_salary_payments', 'teachers'
];
foreach ($tablesToTruncate as $t) { DB::table($t)->truncate(); }

function generateName($gender) {
    $firstNamesMale = ['Kasun', 'Lahiru', 'Chamara', 'Nuwan', 'Supun', 'Gayan', 'Asanka', 'Dilan', 'Tharindu', 'Roshan', 'Kamal', 'Nimal', 'Sunil', 'Saman', 'Ruwan', 'Pradeep', 'Dinesh', 'Nadeeshan', 'Chathura', 'Isuru'];
    $firstNamesFemale = ['Nethmi', 'Sanduni', 'Tharushi', 'Kavindi', 'Hiruni', 'Malithi', 'Hashini', 'Sachini', 'Hansini', 'Sewwandi', 'Amala', 'Kamala', 'Vimala', 'Nayana', 'Damayanthi', 'Nirosha', 'Upeksha', 'Anuradha', 'Chathurika', 'Dilhani'];
    $lastNames = ['Perera', 'Silva', 'Fernando', 'de Silva', 'Bandara', 'Rajapaksa', 'Dissanayake', 'Gunawardena', 'Jayasuriya', 'Karunaratne', 'Jayawardena', 'Senanayake', 'Wickremasinghe', 'Herath', 'Ekanayake', 'Ranasinghe', 'Gunaratne', 'Wijesinghe', 'Samaranayake', 'Munasinghe'];

    $first = $gender === 'Male' ? $firstNamesMale[array_rand($firstNamesMale)] : $firstNamesFemale[array_rand($firstNamesFemale)];
    $last = $lastNames[array_rand($lastNames)];
    $initial = substr($first, 0, 1) . '. ' . $last;
    return ['first_name' => $first, 'last_name' => $last, 'full_name' => "$first $last", 'initial' => $initial];
}

function randomPhone() {
    return '07' . rand(1,8) . rand(1000000, 9999999);
}

// Create Categories
$revCatMonthly = RevenueCategory::updateOrCreate(['name' => 'Monthly Fee'], ['payment_type' => 'monthly', 'interval_months' => 1, 'reminder_days_before' => 5, 'applies_to_all' => 1, 'active' => 1]);
$revCatAdmission = RevenueCategory::updateOrCreate(['name' => 'Admission Fee'], ['payment_type' => 'one-time', 'reminder_days_before' => 0, 'applies_to_all' => 1, 'active' => 1]);

$expCatUtility = ExpenseCategory::updateOrCreate(['name' => 'Utility Bills'], ['active' => 1]);
$expCatStationery = ExpenseCategory::updateOrCreate(['name' => 'Stationery'], ['active' => 1]);
$expCatMaint = ExpenseCategory::updateOrCreate(['name' => 'Maintenance'], ['active' => 1]);

// Create Classes
$classesData = [
    ['name' => 'Preschool', 'level' => 0, 'fee' => 3000],
    ['name' => 'Nursery', 'level' => 1, 'fee' => 4000],
    ['name' => 'Grade 1', 'level' => 2, 'fee' => 5000],
    ['name' => 'Grade 2', 'level' => 3, 'fee' => 5000],
    ['name' => 'Grade 3', 'level' => 4, 'fee' => 5000],
    ['name' => 'Grade 4', 'level' => 5, 'fee' => 5500],
    ['name' => 'Grade 5', 'level' => 6, 'fee' => 5500],
    ['name' => 'Grade 6', 'level' => 7, 'fee' => 6000],
    ['name' => 'Grade 7', 'level' => 8, 'fee' => 6000],
    ['name' => 'Grade 8', 'level' => 9, 'fee' => 6500],
    ['name' => 'Grade 9', 'level' => 10, 'fee' => 6500],
    ['name' => 'Grade 10', 'level' => 11, 'fee' => 7000],
    ['name' => 'Grade 11', 'level' => 12, 'fee' => 7500],
];

$classRooms = [];
foreach ($classesData as $c) {
    $classRooms[] = ClassRoom::updateOrCreate(
        ['name' => $c['name']],
        [
            'level' => $c['level'], 
            'monthly_fee' => $c['fee'], 
            'monthly_fee_revenue_category_id' => $revCatMonthly->id,
            'active' => 1
        ]
    );
}

// Generate Teachers
$teachers = [];
for ($i=1; $i<=50; $i++) {
    $gender = rand(0,1) ? 'Male' : 'Female';
    $nameData = generateName($gender);
    $salary = rand(30, 150) * 1000;
    
    $teachers[] = Teacher::create([
        'name' => $nameData['full_name'],
        'phone' => randomPhone(),
        'joining_date' => Carbon::now()->subMonths(rand(6, 60))->toDateString(),
        'salary_amount' => $salary,
        'epf_enabled' => 1,
        'etf_enabled' => 1,
        'active' => 1
    ]);
}

// Generate Students
$students = [];
for ($i=1; $i<=200; $i++) {
    $gender = rand(0,1) ? 'Male' : 'Female';
    $nameData = generateName($gender);
    $cr = $classRooms[array_rand($classRooms)];
    $hasDue = rand(0,1);
    $dueAmount = $hasDue ? ($cr->monthly_fee * rand(1,3)) : 0;
    
    $students[] = Student::create([
        'admission_number' => 'STU-'.str_pad($i, 4, '0', STR_PAD_LEFT),
        'name' => $nameData['full_name'],
        'first_name' => $nameData['first_name'],
        'name_with_initial' => $nameData['initial'],
        'gender' => $gender,
        'address' => rand(10,999) . ', Galle Road, Colombo',
        'phone' => randomPhone(),
        'guardian_name' => generateName($gender)['full_name'],
        'guardian_phone' => randomPhone(),
        'joining_date' => Carbon::now()->subMonths(rand(1, 24))->toDateString(),
        'fee_start_date' => Carbon::now()->subMonths(rand(1, 12))->startOfMonth()->toDateString(),
        'class_room_id' => $cr->id,
        'monthly_fee' => $cr->monthly_fee,
        'due_amount' => $dueAmount,
        'active' => 1,
        'nationality' => 'Sri Lankan',
        'long_term_medication' => 0,
        'learning_disabilities' => 0,
        'has_siblings_in_college' => 0,
        'use_guardian' => 0,
        'admission_agree' => 1,
        'alumni' => 0,
        'leaving_docs_issued' => 0
    ]);
}

// Generate Teacher Salary Payments
foreach ($teachers as $teacher) {
    for ($m=1; $m<=2; $m++) {
        TeacherSalaryPayment::create([
            'teacher_id' => $teacher->id,
            'amount' => $teacher->salary_amount,
            'base_salary' => $teacher->salary_amount,
            'total_deductions' => 0,
            'paid_at' => Carbon::now()->subMonths($m)->endOfMonth()->toDateString(),
            'payment_month' => Carbon::now()->subMonths($m)->format('Y-m'),
            'payment_method' => 'Bank Transfer'
        ]);
    }
}

// Generate Student Revenue Collections
foreach ($students as $student) {
    // 3 payments each
    for ($p=1; $p<=3; $p++) {
        $paymentDate = Carbon::now()->subMonths($p)->addDays(rand(1,28));
        $rev = Revenue::create([
            'bill_no' => 'BILL-'.rand(10000,999999) . '-' . $student->id . '-' . $p,
            'student_id' => $student->id,
            'revenue_category_id' => $revCatMonthly->id,
            'amount' => $student->monthly_fee,
            'payment_method' => 'Cash',
            'payment_status' => 'paid',
            'paid_at' => $paymentDate->toDateString(),
            'notes' => 'Monthly fee payment'
        ]);
        
        StudentMonthFeeAllocation::create([
            'revenue_id' => $rev->id,
            'student_id' => $student->id,
            'month' => $paymentDate->month,
            'year' => $paymentDate->year,
            'type' => 'due',
            'applied_amount' => $student->monthly_fee,
            'is_partial' => 0,
            'remaining_for_month' => 0
        ]);
    }
}

// Generate some expenses
$expCats = [$expCatUtility->id, $expCatStationery->id, $expCatMaint->id];
for ($e=1; $e<=30; $e++) {
    Expense::create([
        'expense_category_id' => $expCats[array_rand($expCats)],
        'amount' => rand(10, 500) * 100,
        'payment_method' => 'Cash',
        'expense_date' => Carbon::now()->subDays(rand(1, 60))->toDateString(),
        'notes' => 'Sample expense for testing'
    ]);
}

DB::statement('SET FOREIGN_KEY_CHECKS=1;');
echo "Sample data populated successfully.\n";
