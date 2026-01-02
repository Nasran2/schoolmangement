<?php

namespace Tests\Unit;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Services\Billing\MonthlyFeeAllocator;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MonthlyFeeAllocatorTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
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

    /** @test */
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
        $this->assertCount(2, $res['allocations']);
        $this->assertSame('advance', $res['allocations'][0]['type']);
        $this->assertSame('advance', $res['allocations'][1]['type']);
        $this->assertFalse($res['allocations'][0]['is_partial']);
        $this->assertTrue($res['summary']['unallocated_balance'] >= 0);
    }
}
