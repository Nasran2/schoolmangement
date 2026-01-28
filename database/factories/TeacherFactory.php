<?php

namespace Database\Factories;

use App\Models\ClassRoom;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        $joiningDate = $this->faker->dateTimeBetween('-8 years', '-1 month');
        $paymentStartDate = (clone $joiningDate);
        $paymentStartDate = $this->faker->boolean(85)
            ? $this->faker->dateTimeBetween($joiningDate, 'now')
            : null;

        $classNames = ClassRoom::query()->pluck('name')->all();
        $assigned = [];
        if (!empty($classNames) && $this->faker->boolean(80)) {
            $assigned = $this->faker->randomElements(
                $classNames,
                $this->faker->numberBetween(1, min(3, count($classNames)))
            );
        }

        $salaryAmount = $this->faker->randomFloat(2, 35000, 150000);

        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->optional()->address(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'joining_date' => $joiningDate,
            'payment_start_date' => $paymentStartDate,
            'assigned_classes' => empty($assigned) ? null : implode(', ', $assigned),
            'salary_amount' => $salaryAmount,
            'salary_components' => $this->faker->boolean(70)
                ? [
                    ['name' => 'Basic', 'amount' => $salaryAmount],
                    ['name' => 'Allowance', 'amount' => $this->faker->randomFloat(2, 0, 20000)],
                ]
                : null,
            'epf_enabled' => $this->faker->boolean(85),
            'etf_enabled' => $this->faker->boolean(85),
            'active' => $this->faker->boolean(92),
        ];
    }
}
