<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\Student;
use App\Services\AuditLogger;
use App\Services\Billing\BillNumberService;
use App\Services\Revenue\RevenueCategoryScheduleService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RevenueCategoryCollectionController extends Controller
{
    public function class(Request $request, RevenueCategory $category, ClassRoom $classRoom, RevenueCategoryScheduleService $schedule): View
    {
        // Guard: ensure class is applicable
        if (! $category->applies_to_all) {
            $category->load('classRooms');
            abort_unless($category->classRooms->contains('id', $classRoom->id), 404);
        }

        $cycle = null;
        if ($request->filled('due')) {
            try {
                $cycle = $schedule->cycleForDueDate($category, Carbon::parse((string) $request->query('due')));
            } catch (\Throwable $e) {
                $cycle = null;
            }
        }
        if (! $cycle) {
            $cycle = $schedule->currentCycle($category, now());
        }
        $cycleStart = $cycle['start'] ?? null;
        $cycleDue = $cycle['due'] ?? null;

        // Amount for this class: pivot override if present, else category default
        $amount = null;
        $category->load('classRooms');
        $pivot = $category->classRooms->firstWhere('id', $classRoom->id)?->pivot;
        if ($pivot && $pivot->amount !== null) {
            $amount = (float) $pivot->amount;
        } elseif ($category->default_amount !== null) {
            $amount = (float) $category->default_amount;
        }

        $students = Student::query()
            ->where('active', true)
            ->where('class_room_id', $classRoom->id)
            ->orderBy('name')
            ->get();

        $paymentsByStudent = collect();
        if ($cycleStart && $cycleDue) {
            $paymentsByStudent = Revenue::query()
                ->where('revenue_category_id', $category->id)
                ->whereIn('student_id', $students->pluck('id'))
                ->whereBetween('paid_at', [$cycleStart->copy()->startOfDay(), $cycleDue->copy()->endOfDay()])
                ->selectRaw('student_id, sum(amount) as total_paid, max(paid_at) as last_paid_at')
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id');
        }

        return view('revenue.categories.class', [
            'category' => $category,
            'classRoom' => $classRoom,
            'cycle' => $cycle,
            'amount' => $amount,
            'students' => $students,
            'paymentsByStudent' => $paymentsByStudent,
        ]);
    }

    public function bulkStore(
        Request $request,
        RevenueCategory $category,
        ClassRoom $classRoom,
        RevenueCategoryScheduleService $schedule,
        BillNumberService $billNumbers,
        AuditLogger $audit
    ): RedirectResponse {
        // Guard class applicability
        if (! $category->applies_to_all) {
            $category->load('classRooms');
            abort_unless($category->classRooms->contains('id', $classRoom->id), 404);
        }

        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'paid_at' => ['required', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        // Determine default per-student amount (class override > category default)
        $category->load('classRooms');
        $pivot = $category->classRooms->firstWhere('id', $classRoom->id)?->pivot;
        $defaultAmount = null;
        if ($pivot && $pivot->amount !== null) {
            $defaultAmount = (float) $pivot->amount;
        } elseif ($category->default_amount !== null) {
            $defaultAmount = (float) $category->default_amount;
        }

        $amount = isset($validated['amount']) && $validated['amount'] !== null
            ? (float) $validated['amount']
            : $defaultAmount;

        if (! $amount || $amount <= 0) {
            return back()->withInput()->withErrors(['amount' => 'Amount is required for bulk payments (set category/class amount or enter amount).']);
        }

        $studentIds = array_values(array_unique(array_map('intval', $validated['student_ids'])));
        $students = Student::query()
            ->where('active', true)
            ->where('class_room_id', $classRoom->id)
            ->whereIn('id', $studentIds)
            ->get();

        if ($students->isEmpty()) {
            return back()->withInput()->withErrors(['student_ids' => 'No valid students selected for this class.']);
        }

        $cycle = null;
        if ($request->filled('due')) {
            try {
                $cycle = $schedule->cycleForDueDate($category, Carbon::parse((string) $request->query('due')));
            } catch (\Throwable $e) {
                $cycle = null;
            }
        }
        if (! $cycle) {
            $cycle = $schedule->currentCycle($category, now());
        }
        $cycleDue = $cycle['due'] ?? null;
        $notes = $validated['notes'] ?? null;
        if (! $notes) {
            $notes = $cycleDue ? ('Bulk payment for cycle due '.$cycleDue->format('d-m-Y')) : 'Bulk payment';
        }

        $created = [];
        DB::transaction(function () use ($students, $category, $request, $billNumbers, $amount, $validated, $notes, &$created) {
            foreach ($students as $s) {
                $billNo = $billNumbers->nextRevenueBillNumber();
                $created[] = Revenue::create([
                    'bill_no' => $billNo,
                    'revenue_category_id' => (int) $category->id,
                    'student_id' => (int) $s->id,
                    'amount' => $amount,
                    'paid_at' => $validated['paid_at'],
                    'notes' => $notes,
                    'created_by' => $request->user()?->id,
                ]);
            }
        });

        foreach ($created as $rev) {
            $audit->log('revenue.bulk_create', $rev, 'Revenue created (bulk)', [
                'bill_no' => $rev->bill_no,
                'amount' => (float) $rev->amount,
                'student_id' => $rev->student_id,
                'revenue_category_id' => $rev->revenue_category_id,
            ]);
        }

        return back()->with('status', 'Bulk payments recorded for '.count($created).' students.');
    }
}
