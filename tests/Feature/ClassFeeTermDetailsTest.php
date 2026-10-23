<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\Student;
use App\Models\User;
use App\Services\Billing\ClassFeePaymentService;
use App\Services\Billing\ClassFeeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClassFeeTermDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->forgetInstance('settings');
        $this->travelTo(Carbon::parse('2026-10-23 12:00:00'));
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Admin', 'web'));
        $this->actingAs($user);
    }

    private function terms(): array
    {
        return [['start' => '2026-01-01', 'end' => '2026-04-30'],
            ['start' => '2026-05-01', 'end' => '2026-08-31'],
            ['start' => '2026-09-01', 'end' => '2026-12-31']];
    }

    private function school(bool $legacy = false): array
    {
        $class = ClassRoom::create(['name' => 'Term details class', 'active' => true]);
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 850, 'exam_fee_start_date' => '2026-04-20']
            + ($legacy ? [] : ['exam_terms' => $this->terms()]));
        $student = Student::create(['name' => 'Term payment student', 'admission_number' => Str::random(6),
            'class_room_id' => $class->id, 'joining_date' => '2025-01-01', 'active' => true, 'alumni' => false]);

        return [$class, $student];
    }

    private function pay(ClassRoom $class, Student $student, string $date, array $extra = []): Revenue
    {
        $schedule = $class->feeSchedules()->where('fee_type', 'exam')->firstOrFail();

        return app(ClassFeePaymentService::class)->collect($class, 'exam', $extra + [
            'student_id' => $student->id, 'cycle' => $schedule->id.':'.$date, 'submission_id' => (string) Str::uuid(),
            'amount' => 850, 'paid_at' => '2026-10-23', 'payment_method' => 'cash',
        ], auth()->user());
    }

    public function test_exam_payments_name_the_paid_term_in_ledger_reports_revenue_list_and_bill(): void
    {
        [$class, $student] = $this->school();
        $payment = $this->pay($class, $student, '2026-09-01', ['notes' => 'Paid by parent']);
        $this->assertSame('Exam Fee — Term 3 (2026) — Paid by parent', $payment->notes);
        $this->assertSame($payment->notes, $payment->display_notes);
        $this->get(route('reports.daily_ledger', ['from' => '2026-10-23', 'to' => '2026-10-23']))->assertOk()
            ->assertSee('Term 3 (2026)')->assertSee('Paid by parent')->assertDontSee('installment due')
            ->assertViewHas('totalIn', 850.0);
        $this->get(route('reports.revenue'))->assertOk()->assertSee('Term 3 (2026)')->assertDontSee('installment due');
        $this->get(route('revenue.items.index'))->assertOk()->assertSee('Term 3 (2026)');
        $this->get(route('revenue.items.receipt', $payment))->assertOk()->assertSee('Term paid:')->assertSee('Term 3 (2026)')->assertDontSee('Installment due:');
    }

    public function test_old_quarterly_receipts_display_term_numbers_without_rewriting_staff_notes_or_payment_data(): void
    {
        [$class, $student] = $this->school(true);
        $payment = $this->pay($class, $student, '2026-04-20');
        $payment->update(['notes' => 'Exam Fee installment due 2026-04-20 — Original receipt note']);
        $this->assertSame('Exam Fee — Term 1 (2026) — Original receipt note', $payment->display_notes);
        $this->get(route('reports.daily_ledger', ['from' => '2026-10-23', 'to' => '2026-10-23']))->assertOk()
            ->assertSee('Term 1 (2026)')->assertSee('Original receipt note')->assertDontSee('installment due')
            ->assertViewHas('totalIn', 850.0);
        $this->assertSame('Exam Fee installment due 2026-04-20 — Original receipt note', $payment->fresh()->notes);
        $this->assertSame('2026-04-20', $payment->fresh()->class_fee_due_date->toDateString());
        $this->assertSame(850.0, (float) $payment->fresh()->amount);
    }

    public function test_converted_legacy_notes_use_the_mapped_term_and_keep_the_original_manual_note(): void
    {
        [$class, $student] = $this->school(true);
        $payment = $this->pay($class, $student, '2026-07-20');
        $payment->update(['notes' => 'Exam Fee installment due 2026-07-20 — Earlier collection']);
        app(ClassFeeService::class)->configure($class, ['exam_fee' => 850, 'exam_terms' => $this->terms()]);
        $this->assertSame('2026-05-01', $payment->fresh()->class_fee_due_date->toDateString());
        $this->assertSame('Exam Fee — Term 2 (2026) — Earlier collection', $payment->fresh()->display_notes);
    }

    public function test_paying_multiple_terms_in_bulk_lists_every_paid_term_and_keeps_bill_amounts_separate(): void
    {
        [$class, $student] = $this->school();
        $schedule = $class->feeSchedules()->firstOrFail();
        $lines = collect(['2026-01-01', '2026-05-01'])->map(fn ($date) => [
            'student_id' => $student->id, 'class_room_id' => $class->id, 'cycle' => $schedule->id.':'.$date,
            'amount' => 850, 'record_revenue' => 1,
        ])->all();
        $this->post(route('class-fees.bulk.store'), ['submission_id' => (string) Str::uuid(), 'year' => 2026,
            'paid_at' => '2026-10-23', 'payment_method' => 'cash', 'lines' => $lines])->assertSessionHasNoErrors();
        $this->get(route('reports.daily_ledger', ['from' => '2026-10-23', 'to' => '2026-10-23']))->assertOk()
            ->assertSee('Term 1 (2026)')->assertSee('Term 2 (2026)')->assertDontSee('Term 3 (2026)')
            ->assertDontSee('installment due')->assertViewHas('totalIn', 1700.0);
        $this->assertDatabaseCount('revenues', 2);
        foreach (Revenue::orderBy('id')->get() as $index => $payment) {
            $this->get(route('revenue.items.receipt', $payment))->assertOk()->assertSee('Term '.($index + 1).' (2026)')->assertDontSee('Installment due:');
        }
    }

    public function test_term_number_comes_from_the_calendar_when_terms_span_two_years(): void
    {
        [$class, $student] = $this->school();
        $schedule = $class->feeSchedules()->firstOrFail();
        $schedule->update(['first_due_date' => '2026-09-01', 'terms' => [
            ['start' => '2026-09-01', 'end' => '2026-12-31'],
            ['start' => '2027-01-01', 'end' => '2027-04-30'],
            ['start' => '2027-05-01', 'end' => '2027-08-31'],
        ]]);
        $payment = $this->pay($class, $student, '2027-01-01');
        $this->assertSame('Term 2 (2027)', $payment->exam_term_label);
    }

    public function test_revenue_pdf_uses_the_same_term_description_for_existing_payments(): void
    {
        [$class, $student] = $this->school();
        $payment = $this->pay($class, $student, '2026-05-01');
        $payment->update(['notes' => 'Exam Fee installment due 2026-05-01']);
        Pdf::shouldReceive('loadHTML')->once()->withArgs(fn ($html) => str_contains($html, 'Term 2 (2026)') && ! str_contains($html, 'installment due'))->andReturnSelf();
        Pdf::shouldReceive('setPaper')->andReturnSelf();
        Pdf::shouldReceive('setOption')->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('PDF preview'));
        $this->get(route('reports.revenue', ['pdf' => 1]))->assertOk();
    }

    public function test_ordinary_revenue_and_cancelled_payment_descriptions_are_preserved(): void
    {
        [$class, $student] = $this->school();
        $payment = $this->pay($class, $student, '2026-01-01');
        app(ClassFeePaymentService::class)->cancel($payment, 'Wrong payment', auth()->user());
        $category = RevenueCategory::create(['name' => 'Other income', 'active' => true, 'payment_type' => 'one_time']);
        $other = Revenue::create(['revenue_category_id' => $category->id, 'amount' => 100, 'paid_at' => '2026-10-23', 'notes' => 'Original ordinary note', 'payment_method' => 'cash', 'payment_status' => 'confirmed']);
        $this->assertSame('Original ordinary note', $other->display_notes);
        $this->get(route('reports.daily_ledger', ['from' => '2026-10-23', 'to' => '2026-10-23']))->assertOk()
            ->assertSee('Cancelled - Wrong payment')->assertSee('Original ordinary note')->assertViewHas('totalIn', 100.0);
    }
}
