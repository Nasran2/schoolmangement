<?php

namespace Database\Factories;

use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        $fullName = $firstName . ' ' . $lastName;

        $joiningDate = $this->faker->dateTimeBetween('-6 years', '-1 week');
        $feeStartDate = $this->faker->boolean(85)
            ? $this->faker->dateTimeBetween($joiningDate, 'now')
            : null;

        $classRoom = ClassRoom::query()->inRandomOrder()->first();
        $monthlyFee = $classRoom?->monthly_fee ?? $this->faker->randomFloat(2, 500, 8000);

        $useGuardian = $this->faker->boolean(25);

        return [
            'admission_number' => 'ADM-' . $this->faker->unique()->numerify('######'),
            'name' => $fullName,
            'first_name' => $firstName,
            'other_names' => null,
            'name_with_initial' => $firstName[0] . '. ' . $lastName,
            'address' => $this->faker->optional()->address(),
            'parent_address' => $this->faker->optional()->address(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'whatsapp_number' => $this->faker->optional()->phoneNumber(),
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'date_of_birth' => $this->faker->dateTimeBetween('-18 years', '-5 years'),

            'guardian_name' => $useGuardian ? $this->faker->name() : null,
            'guardian_phone' => $useGuardian ? $this->faker->phoneNumber() : null,
            'guardian_relationship' => $useGuardian ? $this->faker->randomElement(['Uncle', 'Aunt', 'Grandparent', 'Sibling', 'Guardian']) : null,
            'use_guardian' => $useGuardian,

            'joining_date' => $joiningDate,
            'fee_start_date' => $feeStartDate,

            'year' => (string) now()->year,
            'class' => $classRoom?->name,
            'class_room_id' => $classRoom?->id,
            'promoted_until_year' => null,

            'religion' => $this->faker->optional()->randomElement(['Buddhism', 'Hinduism', 'Islam', 'Christianity']),
            'desired_class' => $this->faker->optional()->randomElement(['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5']),

            'medical_history' => null,
            'long_term_medication' => $this->faker->boolean(5),
            'learning_disabilities' => $this->faker->boolean(3),
            'previous_school' => $this->faker->optional()->company() . ' School',
            'previous_grade' => $this->faker->optional()->randomElement(['KG', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5']),
            'siblings' => null,
            'has_siblings_in_college' => $this->faker->boolean(10),

            'father_name_with_initial' => $this->faker->optional()->name(),
            'father_nic_passport' => null,
            'father_religion' => null,
            'father_nationality' => $this->faker->optional()->country(),
            'father_occupation' => $this->faker->optional()->jobTitle(),
            'father_phone' => $this->faker->optional()->phoneNumber(),
            'father_whatsapp' => $this->faker->optional()->phoneNumber(),
            'father_office_phone' => null,
            'father_emergency_number' => $this->faker->optional()->phoneNumber(),

            'mother_name_with_initial' => $this->faker->optional()->name(),
            'mother_nic_passport' => null,
            'mother_religion' => null,
            'mother_nationality' => $this->faker->optional()->country(),
            'mother_occupation' => $this->faker->optional()->jobTitle(),
            'mother_phone' => $this->faker->optional()->phoneNumber(),
            'mother_whatsapp' => $this->faker->optional()->phoneNumber(),
            'mother_office_phone' => null,
            'mother_emergency_number' => $this->faker->optional()->phoneNumber(),

            'passport_photo_path' => null,
            'admission_agree' => $this->faker->boolean(70),
            'nationality' => $this->faker->randomElement(['Sri Lankan', 'Indian', 'Bangladeshi', 'Pakistani']),
            'hear_about_us' => $this->faker->optional()->randomElement(['Facebook', 'Friend', 'Google', 'Newspaper', 'Other']),

            'leaving_docs_issued' => false,
            'alumni' => false,

            'monthly_fee' => $monthlyFee,
            'due_amount' => 0,
            'active' => $this->faker->boolean(92),
        ];
    }
}
