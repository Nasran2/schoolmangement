<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RbacSeeder::class,
            DefaultRevenueCategoriesSeeder::class,
        ]);

        if (env('SEED_SAMPLE_DATA', false)) {
            $this->call([
                SampleTeachersAndStudentsSeeder::class,
            ]);
        }
    }
}
