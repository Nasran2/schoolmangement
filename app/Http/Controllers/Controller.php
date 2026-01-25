<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;

abstract class Controller
{
    protected function teacherExpenseCategory(): ExpenseCategory
    {
        return ExpenseCategory::firstOrCreate(
            ['name' => 'Visiting Teacher Payment'],
            [
                'description' => 'Payments made to visiting instructors for extra classes and seminars.',
                'active' => true,
            ]
        );
    }
}
