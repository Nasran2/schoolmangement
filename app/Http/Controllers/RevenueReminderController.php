<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\Student;
use App\Services\Revenue\RevenueCategoryScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RevenueReminderController extends Controller
{
    public function index(Request $request, RevenueCategoryScheduleService $schedule): View
    {
        $days = (int) $request->query('days', 7);
        $days = max(1, min(60, $days));

        $start = now()->startOfDay();
        $end = now()->addDays($days)->endOfDay();

        $categories = RevenueCategory::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $rows = [];

        foreach ($categories as $cat) {
            $cycle = $schedule->currentCycle($cat, now());
            if (!$cycle) {
                continue; // one-time / not scheduled
            }

            $reminder = $cycle['reminder'] ?? null;
            $due = $cycle['due'] ?? null;
            $cycleStart = $cycle['start'] ?? null;
            if (!$reminder || !$due || !$cycleStart) {
                continue;
            }

            // Only show reminders due in next X days OR already due for this upcoming payment
            if ($reminder->gt($end)) {
                continue;
            }
            if ($due->lt($start)) {
                continue;
            }

            $cat->load('classRooms');

            $classRoomsQuery = ClassRoom::query()
                ->orderByRaw('level is null')
                ->orderBy('level')
                ->orderBy('name');

            $classRooms = $cat->applies_to_all
                ? $classRoomsQuery->get()
                : $classRoomsQuery->whereIn('id', $cat->classRooms->pluck('id'))->get();

            $amountOverrides = $cat->classRooms
                ->mapWithKeys(fn ($cr) => [(int) $cr->id => ($cr->pivot?->amount !== null ? (float) $cr->pivot->amount : null)])
                ->toArray();

            $studentCounts = Student::query()
                ->where('active', true)
                ->whereIn('class_room_id', $classRooms->pluck('id'))
                ->selectRaw('class_room_id, count(*) as total')
                ->groupBy('class_room_id')
                ->pluck('total', 'class_room_id');

            $paidRows = Revenue::query()
                ->join('students', 'students.id', '=', 'revenues.student_id')
                ->where('revenues.revenue_category_id', $cat->id)
                ->whereNotNull('revenues.student_id')
                ->whereBetween('revenues.paid_at', [$cycleStart->copy()->startOfDay(), $due->copy()->endOfDay()])
                ->selectRaw('students.class_room_id as class_room_id, count(distinct revenues.student_id) as paid_students, sum(revenues.amount) as paid_amount')
                ->groupBy('students.class_room_id')
                ->get();

            $paidCounts = $paidRows->pluck('paid_students', 'class_room_id');
            $paidAmounts = $paidRows->pluck('paid_amount', 'class_room_id');

            $expected = 0.0;
            $totalStudents = 0;
            $paidStudents = 0;
            foreach ($classRooms as $cr) {
                $count = (int) ($studentCounts[$cr->id] ?? 0);
                $totalStudents += $count;
                $paidStudents += (int) ($paidCounts[$cr->id] ?? 0);

                $amt = $amountOverrides[$cr->id] ?? ($cat->default_amount !== null ? (float) $cat->default_amount : null);
                if ($amt !== null) {
                    $expected += $count * $amt;
                }
            }

            $paidAmount = (float) $paidAmounts->sum(fn ($v) => (float) ($v ?? 0));

            $rows[] = [
                'category' => $cat,
                'cycle' => $cycle,
                'reminder' => $reminder,
                'due' => $due,
                'total_students' => $totalStudents,
                'paid_students' => $paidStudents,
                'expected_amount' => $expected,
                'paid_amount' => $paidAmount,
                'is_overdue_reminder' => $reminder->lt($start),
            ];
        }

        // Sort: overdue reminders first, then soonest
        usort($rows, function ($a, $b) {
            /** @var Carbon $ra */
            $ra = $a['reminder'];
            /** @var Carbon $rb */
            $rb = $b['reminder'];
            return $ra->getTimestamp() <=> $rb->getTimestamp();
        });

        return view('revenue.reminders.index', [
            'days' => $days,
            'start' => $start,
            'end' => $end,
            'rows' => $rows,
        ]);
    }
}
