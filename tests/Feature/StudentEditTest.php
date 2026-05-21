<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\StudentPromotionHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Tests\TestCase;

class StudentEditTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_student_edit_updates_selected_grade(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 21, 14, 30));

        $this->withoutMiddleware(PermissionMiddleware::class);

        $oldClass = ClassRoom::create([
            'name' => 'Grade 1',
            'level' => 1,
            'monthly_fee' => 8000,
            'active' => true,
        ]);

        $newClass = ClassRoom::create([
            'name' => 'Grade 2',
            'level' => 2,
            'monthly_fee' => 7250,
            'active' => true,
        ]);

        $student = Student::create([
            'admission_number' => 'STU-001',
            'name' => 'Old Name',
            'first_name' => 'Old',
            'name_with_initial' => 'O. Name',
            'parent_address' => 'Old address',
            'gender' => 'Male',
            'date_of_birth' => '2018-01-01',
            'use_guardian' => true,
            'guardian_name' => 'Guardian',
            'guardian_relationship' => 'Parent',
            'guardian_phone' => '0770000000',
            'joining_date' => '2026-04-01',
            'fee_start_date' => '2026-04-01',
            'year' => '2026-2027',
            'class_room_id' => $oldClass->id,
            'class' => $oldClass->name,
            'religion' => 'Buddhism',
            'long_term_medication' => false,
            'learning_disabilities' => false,
            'has_siblings_in_college' => false,
            'monthly_fee' => 8000,
            'due_amount' => 16000,
            'active' => true,
        ]);

        $response = $this
            ->actingAs(User::factory()->create())
            ->put(route('students.update', $student), [
                'admission_number' => 'STU-001',
                'name' => 'Updated Name',
                'first_name' => 'Updated',
                'name_with_initial' => 'U. Name',
                'parent_address' => 'Updated address',
                'gender' => 'Male',
                'date_of_birth' => '01-01-2018',
                'use_guardian' => '1',
                'guardian_name' => 'Guardian',
                'guardian_relationship' => 'Parent',
                'guardian_phone' => '0770000000',
                'joining_date' => '01-04-2026',
                'fee_start_date' => '01-04-2026',
                'class_room_id' => $newClass->id,
                'monthly_fee' => '7250',
                'active' => '1',
                'religion' => 'Buddhism',
                'long_term_medication' => '0',
                'learning_disabilities' => '0',
                'has_siblings_in_college' => '0',
                'leaving_docs_issued' => '0',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status', 'Student updated.');

        $student->refresh();

        $this->assertSame($newClass->id, $student->class_room_id);
        $this->assertSame('Grade 2', $student->class);
        $this->assertSame('7250.00', (string) $student->monthly_fee);
        $this->assertSame('14500.00', (string) $student->due_amount);

        $this->assertDatabaseHas('student_promotion_histories', [
            'student_id' => $student->id,
            'from_class_room_id' => $oldClass->id,
            'to_class_room_id' => $newClass->id,
            'action' => 'promote',
            'notes' => 'Grade changed from student edit.',
        ]);

        $this->assertDatabaseHas('student_monthly_fee_overrides', [
            'student_id' => $student->id,
            'year' => 2026,
            'month' => 4,
            'fee_amount' => '7250.00',
        ]);
        $this->assertDatabaseHas('student_monthly_fee_overrides', [
            'student_id' => $student->id,
            'year' => 2026,
            'month' => 5,
            'fee_amount' => '7250.00',
        ]);
    }

    public function test_student_edit_shows_clear_guardian_validation_errors(): void
    {
        $this->withoutMiddleware(PermissionMiddleware::class);

        $class = ClassRoom::create([
            'name' => 'Grade 1',
            'level' => 1,
            'monthly_fee' => 5000,
            'active' => true,
        ]);

        $student = Student::create([
            'admission_number' => 'STU-002',
            'name' => 'Guardian Test',
            'first_name' => 'Guardian',
            'name_with_initial' => 'G. Test',
            'parent_address' => 'Address',
            'gender' => 'Male',
            'date_of_birth' => '2018-01-01',
            'use_guardian' => true,
            'guardian_name' => 'Old Guardian',
            'guardian_relationship' => 'Parent',
            'guardian_phone' => '0770000000',
            'joining_date' => '2026-01-01',
            'fee_start_date' => '2026-01-01',
            'year' => '2026-2027',
            'class_room_id' => $class->id,
            'class' => $class->name,
            'religion' => 'Buddhism',
            'long_term_medication' => false,
            'learning_disabilities' => false,
            'has_siblings_in_college' => false,
            'monthly_fee' => 5000,
            'due_amount' => 5000,
            'active' => true,
        ]);

        $response = $this
            ->actingAs(User::factory()->create())
            ->from(route('students.edit', $student))
            ->put(route('students.update', $student), [
                'admission_number' => 'STU-002',
                'name' => 'Guardian Test',
                'first_name' => 'Guardian',
                'name_with_initial' => 'G. Test',
                'parent_address' => 'Address',
                'gender' => 'Male',
                'date_of_birth' => '01-01-2018',
                'use_guardian' => '1',
                'guardian_name' => '',
                'guardian_relationship' => '',
                'guardian_phone' => '0770000000',
                'joining_date' => '01-01-2026',
                'fee_start_date' => '01-01-2026',
                'class_room_id' => $class->id,
                'monthly_fee' => '5000',
                'active' => '1',
                'religion' => 'Buddhism',
                'long_term_medication' => '0',
                'learning_disabilities' => '0',
                'has_siblings_in_college' => '0',
                'leaving_docs_issued' => '0',
            ]);

        $response->assertRedirect(route('students.edit', $student));
        $response->assertSessionHasErrors([
            'guardian_name' => 'Guardian Name is required because "Student has a Guardian (No Parents)" is checked.',
            'guardian_relationship' => 'Relationship is required because "Student has a Guardian (No Parents)" is checked.',
        ]);
    }

    public function test_student_edit_applies_changed_monthly_fee_even_when_class_stays_same(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 21, 14, 30));

        $this->withoutMiddleware(PermissionMiddleware::class);

        $previousClass = ClassRoom::create([
            'name' => 'Grade 2',
            'level' => 3,
            'monthly_fee' => 7750,
            'active' => true,
        ]);

        $currentClass = ClassRoom::create([
            'name' => 'Grade 3',
            'level' => 4,
            'monthly_fee' => 8000,
            'active' => true,
        ]);

        $student = Student::create([
            'admission_number' => 'STU-003',
            'name' => 'Fee Change Test',
            'first_name' => 'Fee',
            'name_with_initial' => 'F. Test',
            'parent_address' => 'Address',
            'gender' => 'Female',
            'date_of_birth' => '2018-01-01',
            'use_guardian' => true,
            'guardian_name' => 'Guardian',
            'guardian_relationship' => 'Parent',
            'guardian_phone' => '0770000000',
            'joining_date' => '2026-05-01',
            'fee_start_date' => '2026-05-01',
            'year' => '2026-2027',
            'class_room_id' => $currentClass->id,
            'class' => $currentClass->name,
            'religion' => 'Buddhism',
            'long_term_medication' => false,
            'learning_disabilities' => false,
            'has_siblings_in_college' => false,
            'monthly_fee' => 7750,
            'due_amount' => 7750,
            'active' => true,
        ]);

        StudentPromotionHistory::create([
            'student_id' => $student->id,
            'from_class_room_id' => $previousClass->id,
            'to_class_room_id' => $currentClass->id,
            'action' => 'promote',
            'academic_year' => '2026-2027',
            'notes' => 'Existing server history.',
        ]);

        $response = $this
            ->actingAs(User::factory()->create())
            ->put(route('students.update', $student), [
                'admission_number' => 'STU-003',
                'name' => 'Fee Change Test',
                'first_name' => 'Fee',
                'name_with_initial' => 'F. Test',
                'parent_address' => 'Address',
                'gender' => 'Female',
                'date_of_birth' => '01-01-2018',
                'use_guardian' => '1',
                'guardian_name' => 'Guardian',
                'guardian_relationship' => 'Parent',
                'guardian_phone' => '0770000000',
                'joining_date' => '01-05-2026',
                'fee_start_date' => '01-05-2026',
                'class_room_id' => $currentClass->id,
                'monthly_fee' => '8000',
                'active' => '1',
                'religion' => 'Buddhism',
                'long_term_medication' => '0',
                'learning_disabilities' => '0',
                'has_siblings_in_college' => '0',
                'leaving_docs_issued' => '0',
            ]);

        $response->assertSessionHasNoErrors();

        $student->refresh();

        $this->assertSame($currentClass->id, $student->class_room_id);
        $this->assertSame('8000.00', (string) $student->monthly_fee);
        $this->assertSame('8000.00', (string) $student->due_amount);

        $this->assertDatabaseHas('student_monthly_fee_overrides', [
            'student_id' => $student->id,
            'year' => 2026,
            'month' => 5,
            'fee_amount' => '8000.00',
        ]);
    }
}
