<?php

namespace Database\Seeders;

use App\Models\RevenueCategory;
use Illuminate\Database\Seeder;

class DefaultRevenueCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        RevenueCategory::query()->updateOrCreate(
            ['name' => 'Monthly Fee'],
            [
                'payment_type' => 'monthly',
                'applies_to_all' => true,
                'description' => 'Default monthly tuition fee category.',
                'active' => true,
            ]
        );
    }
}
