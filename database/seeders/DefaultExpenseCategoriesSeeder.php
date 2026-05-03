<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class DefaultExpenseCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Salaries', 'description' => 'Teacher and staff salaries'],
            ['name' => 'Utilities', 'description' => 'Electricity, water, and internet bills'],
            ['name' => 'Maintenance', 'description' => 'Building and facility maintenance'],
            ['name' => 'Supplies', 'description' => 'Office and classroom supplies'],
            ['name' => 'Transportation', 'description' => 'Bus and transport costs'],
            ['name' => 'Insurance', 'description' => 'School insurance and coverage'],
            ['name' => 'Equipment', 'description' => 'Equipment purchases and repairs'],
            ['name' => 'Food & Beverages', 'description' => 'Canteen and meal expenses'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::query()->updateOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'active' => true,
                ]
            );
        }
    }
}
