<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\RevenueCategory;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class SampleTeachersAndStudentsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the default Monthly Fee category exists (used by class rooms).
        RevenueCategory::query()->updateOrCreate(
            ['name' => 'Monthly Fee'],
            [
                'payment_type' => 'monthly',
                'applies_to_all' => true,
                'description' => 'Default monthly tuition fee category.',
                'active' => true,
            ]
        );

        $monthlyFeeCategoryId = RevenueCategory::query()
            ->where('name', 'Monthly Fee')
            ->value('id');

        // Create some class rooms if none exist.
        if (ClassRoom::query()->count() === 0) {
            $levels = [1, 2, 3, 4, 5, 6];
            foreach ($levels as $level) {
                ClassRoom::query()->create([
                    'name' => 'Grade ' . $level,
                    'level' => $level,
                    'description' => 'Sample classroom for Grade ' . $level,
                    'active' => true,
                    'monthly_fee' => fake()->randomFloat(2, 500, 8000),
                    'monthly_fee_revenue_category_id' => $monthlyFeeCategoryId,
                ]);
            }
        }

        $teachersCount = (int) (env('SAMPLE_TEACHERS', 10));
        $studentsCount = (int) (env('SAMPLE_STUDENTS', 50));

        Teacher::factory()->count(max(0, $teachersCount))->create();
        Student::factory()->count(max(0, $studentsCount))->create();
    }
}
