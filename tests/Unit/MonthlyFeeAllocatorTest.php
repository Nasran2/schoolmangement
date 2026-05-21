<?php

namespace Tests\Unit;

use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\RevenueAdjustment;
use App\Models\RevenueCategory;
use App\Models\Student;
use App\Models\StudentMonthFeeAllocation;
use App\Models\StudentPromotionHistory;
use App\Services\Billing\MonthlyFeeAllocator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonthlyFeeAllocatorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function allocates_oldest_dues_first_and_handles_partial_due()
    {
        $class = ClassRoom::create(['name' => 'Grade 1', 'monthly_fee' => 5000]);
        $student = Student::create([
            'admission_number' => 'A001',
            'name' => 'John Doe',
            'class_room_id' => $class->id,
            'class' => $class->name,
            'monthly_fee' => 5000,
            'fee_start_date' => Carbon::now()->subMonths(2)->startOfMonth()->format('Y-m-d'),
            'active' => true,
        ]);

        $svc = new MonthlyFeeAllocator();

        // Pay Rs 3000 -> should mark first due month as partial
        $res = $svc->allocate($student, 3000.0);
        $this->assertCount(1, $res['allocations']);
        $first = $res['allocations'][0];
        $this->assertSame('due', $first['type']);
        $this->assertTrue($first['is_partial']);
        $this->assertEquals(3000.0, $first['applied_amount']);
        $this->assertEquals(2000.0, $first['remaining_for_month']);

        // Pay Rs 10000 -> should fully cover two months
        $res2 = $svc->allocate($student, 10000.0);
        $this->assertCount(2, $res2['allocations']);
        $this->assertFalse($res2['allocations'][0]['is_partial']);
        $this->assertFalse($res2['allocations'][1]['is_partial']);
    }

    #[Test]
    public function allocates_advance_months_when_no_dues()
    {
        $class = ClassRoom::create(['name' => 'Grade 2', 'monthly_fee' => 5000]);
        $student = Student::create([
            'admission_number' => 'A002',
            'name' => 'Jane Roe',
            'class_room_id' => $class->id,
            'class' => $class->name,
            'monthly_fee' => 5000,
            // Start in future to have no dues
            'fee_start_date' => Carbon::now()->addMonth()->startOfMonth()->format('Y-m-d'),
            'active' => true,
        ]);

        $svc = new MonthlyFeeAllocator();
        $nextMonth = Carbon::now()->addMonth();
        $adv = [
            ['month' => (int) $nextMonth->format('n'), 'year' => (int) $nextMonth->format('Y')],
            ['month' => (int) $nextMonth->copy()->addMonth()->format('n'), 'year' => (int) $nextMonth->copy()->addMonth()->format('Y')],
        ];

        $res = $svc->allocate($student, 12000.0, $adv);
        $this->assertCount(3, $res['allocations']);
        $this->assertSame('advance', $res['allocations'][0]['type']);
        $this->assertSame('advance', $res['allocations'][1]['type']);
        $this->assertSame('advance', $res['allocations'][2]['type']);
        $this->assertFalse($res['allocations'][0]['is_partial']);
        $this->assertTrue((bool) $res['allocations'][2]['is_partial']);
        $this->assertTrue($res['summary']['unallocated_balance'] >= 0);
    }

    #[Test]
    public function resolves_monthly_fee_across_multiple_promotions_and_demotions_effective_next_month()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 13, 10, 0, 0));

        $cat = RevenueCategory::create(['name' => 'Monthly Fee', 'payment_type' => 'monthly', 'active' => true]);

        $classOld = ClassRoom::create(['name' => 'Grade Old', 'monthly_fee' => 5000, 'monthly_fee_revenue_category_id' => $cat->id]);
        $classHigh = ClassRoom::create(['name' => 'Grade High', 'monthly_fee' => 7000, 'monthly_fee_revenue_category_id' => $cat->id]);
        $classLow = ClassRoom::create(['name' => 'Grade Low', 'monthly_fee' => 6000, 'monthly_fee_revenue_category_id' => $cat->id]);

        $student = Student::create([
            'admission_number' => 'A100',
            'name' => 'Promo Demo',
            'class_room_id' => $classOld->id,
            'class' => $classOld->name,
            'monthly_fee' => 5000,
            'fee_start_date' => Carbon::create(2025, 9, 1)->format('Y-m-d'),
            'active' => true,
        ]);

        $promote = StudentPromotionHistory::create([
            'student_id' => $student->id,
            'from_class_room_id' => $classOld->id,
            'to_class_room_id' => $classHigh->id,
            'action' => 'promote',
            'academic_year' => '2025',
        ]);
        $promote->created_at = Carbon::create(2025, 10, 15, 9, 0, 0);
        $promote->save();

        $demote = StudentPromotionHistory::create([
            'student_id' => $student->id,
            'from_class_room_id' => $classHigh->id,
            'to_class_room_id' => $classLow->id,
            'action' => 'demote',
            'academic_year' => '2025',
        ]);
        $demote->created_at = Carbon::create(2025, 12, 10, 9, 0, 0);
        $demote->save();

        $svc = new MonthlyFeeAllocator();
        $ledger = $svc->buildLedger($student, 0);

        $this->assertEquals(5000.0, (float) ($ledger['2025-09']['due'] ?? 0));
        $this->assertEquals(5000.0, (float) ($ledger['2025-10']['due'] ?? 0)); // promote effective from Nov
        $this->assertEquals(7000.0, (float) ($ledger['2025-11']['due'] ?? 0));
        $this->assertEquals(7000.0, (float) ($ledger['2025-12']['due'] ?? 0));
        $this->assertEquals(6000.0, (float) ($ledger['2026-01']['due'] ?? 0)); // demote effective from Jan
    }

    #[Test]
    public function legacy_revenue_refund_reduces_applied_amount_in_ledger()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 13, 10, 0, 0));

        $cat = RevenueCategory::create(['name' => 'Monthly Fee', 'payment_type' => 'monthly', 'active' => true]);
        $class = ClassRoom::create(['name' => 'Grade 3', 'monthly_fee' => 5000, 'monthly_fee_revenue_category_id' => $cat->id]);
        $student = Student::create([
            'admission_number' => 'A200',
            'name' => 'Refund Case',
            'class_room_id' => $class->id,
            'class' => $class->name,
            'monthly_fee' => 5000,
            'fee_start_date' => Carbon::create(2025, 12, 1)->format('Y-m-d'),
            'active' => true,
        ]);

        $rev = Revenue::create([
            'student_id' => $student->id,
            'revenue_category_id' => $cat->id,
            'amount' => 5000,
            'paid_at' => Carbon::create(2026, 1, 5)->format('Y-m-d'),
            'bill_no' => 'R-1',
        ]);

        RevenueAdjustment::create([
            'revenue_id' => $rev->id,
            'type' => 'refund',
            'amount' => 2000,
            'reason' => 'Test refund',
        ]);

        $svc = new MonthlyFeeAllocator();
        $ledger = $svc->buildLedger($student, 0);

        // Oldest month is Dec 2025, fee 5000. Revenue net is 3000 after refund -> should be partial.
        $this->assertEquals(3000.0, (float) ($ledger['2025-12']['paid'] ?? 0));
        $this->assertEquals(2000.0, (float) ($ledger['2025-12']['remaining'] ?? 0));
        $this->assertSame('partially_paid', (string) ($ledger['2025-12']['status'] ?? ''));
    }

    #[Test]
    public function rejected_cheque_allocation_does_not_count_as_paid_or_on_hold()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 13, 10, 0, 0));

        $cat = RevenueCategory::create(['name' => 'Monthly Fee', 'payment_type' => 'monthly', 'active' => true]);
        $class = ClassRoom::create(['name' => 'Grade 4', 'monthly_fee' => 5000, 'monthly_fee_revenue_category_id' => $cat->id]);
        $student = Student::create([
            'admission_number' => 'A300',
            'name' => 'Returned Cheque',
            'class_room_id' => $class->id,
            'class' => $class->name,
            'monthly_fee' => 5000,
            'fee_start_date' => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'active' => true,
        ]);

        $rev = Revenue::create([
            'student_id' => $student->id,
            'revenue_category_id' => $cat->id,
            'amount' => 5000,
            'paid_at' => Carbon::create(2026, 1, 5)->format('Y-m-d'),
            'bill_no' => 'R-CHEQUE-1',
            'payment_method' => 'cheque',
            'payment_status' => 'rejected',
            'cheque_date' => Carbon::create(2026, 1, 20)->format('Y-m-d'),
            'confirmed_at' => Carbon::create(2026, 1, 13, 10, 0, 0),
        ]);

        StudentMonthFeeAllocation::create([
            'revenue_id' => $rev->id,
            'student_id' => $student->id,
            'month' => 1,
            'year' => 2026,
            'type' => 'due',
            'applied_amount' => 5000,
            'is_partial' => false,
            'remaining_for_month' => 0,
        ]);

        $svc = new MonthlyFeeAllocator();
        $ledger = $svc->buildLedger($student, 0);
        $holdCoverage = $svc->buildHoldCoverage($student, 0);

        $this->assertEquals(0.0, (float) ($ledger['2026-01']['paid'] ?? 0));
        $this->assertEquals(5000.0, (float) ($ledger['2026-01']['remaining'] ?? 0));
        $this->assertSame('unpaid', (string) ($ledger['2026-01']['status'] ?? ''));
        $this->assertEquals(0.0, (float) ($holdCoverage['2026-01'] ?? 0));
    }

    #[Test]
    public function rebuilds_allocations_after_fee_start_date_changes()
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 12, 10, 0, 0));

        $cat = RevenueCategory::create(['name' => 'Monthly Fee', 'payment_type' => 'monthly', 'active' => true]);
        $class = ClassRoom::create(['name' => 'Grade 5', 'monthly_fee' => 7750, 'monthly_fee_revenue_category_id' => $cat->id]);
        $student = Student::create([
            'admission_number' => 'A400',
            'name' => 'Start Date Change',
            'class_room_id' => $class->id,
            'class' => $class->name,
            'monthly_fee' => 7750,
            'fee_start_date' => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'active' => true,
        ]);

        $revenue = Revenue::create([
            'student_id' => $student->id,
            'revenue_category_id' => $cat->id,
            'amount' => 15500,
            'paid_at' => Carbon::create(2026, 2, 5)->format('Y-m-d'),
            'bill_no' => 'R-START-1',
            'payment_status' => 'confirmed',
        ]);

        StudentMonthFeeAllocation::create([
            'revenue_id' => $revenue->id,
            'student_id' => $student->id,
            'month' => 1,
            'year' => 2026,
            'type' => 'due',
            'applied_amount' => 7750,
            'is_partial' => false,
            'remaining_for_month' => 0,
        ]);
        StudentMonthFeeAllocation::create([
            'revenue_id' => $revenue->id,
            'student_id' => $student->id,
            'month' => 2,
            'year' => 2026,
            'type' => 'due',
            'applied_amount' => 7750,
            'is_partial' => false,
            'remaining_for_month' => 0,
        ]);

        $student->forceFill(['fee_start_date' => Carbon::create(2026, 4, 1)->format('Y-m-d')])->save();

        $svc = new MonthlyFeeAllocator();
        $ledgerBeforeRebuild = $svc->buildLedger($student->refresh(), 0);

        $this->assertSame('paid', (string) ($ledgerBeforeRebuild['2026-04']['status'] ?? ''));
        $this->assertSame('paid', (string) ($ledgerBeforeRebuild['2026-05']['status'] ?? ''));

        $svc->rebuildAllocationsForStudent($student->refresh());
        $ledger = $svc->buildLedger($student->refresh(), 0);

        $this->assertDatabaseMissing('student_month_fee_allocations', [
            'student_id' => $student->id,
            'year' => 2026,
            'month' => 1,
        ]);
        $this->assertSame('paid', (string) ($ledger['2026-04']['status'] ?? ''));
        $this->assertSame('paid', (string) ($ledger['2026-05']['status'] ?? ''));
        $this->assertEquals(0.0, $student->refresh()->computeMonthlyDue());
    }
}
