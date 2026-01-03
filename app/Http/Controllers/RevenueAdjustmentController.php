<?php

namespace App\Http\Controllers;

use App\Models\Revenue;
use App\Models\RevenueAdjustment;
use App\Models\RevenueCategory;
use App\Models\Student;
use App\Services\AuditLogger;
use App\Services\Billing\RevenueAdjustmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RevenueAdjustmentController extends Controller
{
    public function index(Request $request): View
    {
        $billNo = trim((string) $request->query('bill_no', ''));
        $q = trim((string) $request->query('q', ''));

        $revenue = null;
        $rows = collect();
        $message = null;

        if ($billNo !== '') {
            $revenue = Revenue::query()
                ->with(['category', 'student', 'allocations', 'adjustments.creator'])
                ->where('bill_no', $billNo)
                ->first();

            if (! $revenue) {
                $message = 'No bill found for Bill No: ' . $billNo;
            } else {
                $rows = collect([$this->buildRow($revenue)]);
            }
        } elseif ($q !== '') {
            $like = '%' . str_replace('%', '\\%', $q) . '%';
            $rows = Revenue::query()
                ->with(['category', 'student', 'adjustments.creator'])
                ->leftJoin('students', 'students.id', '=', 'revenues.student_id')
                ->select('revenues.*')
                ->where(function ($sub) use ($like) {
                    $sub->where('revenues.bill_no', 'like', $like)
                        ->orWhere('students.name', 'like', $like)
                        ->orWhere('students.admission_number', 'like', $like)
                        ->orWhere('students.phone', 'like', $like)
                        ->orWhere('students.whatsapp_number', 'like', $like);
                })
                ->orderByDesc('revenues.paid_at')
                ->limit(50)
                ->get()
                ->map(fn (Revenue $r) => $this->buildRow($r));

            if ($rows->isEmpty()) {
                $message = 'No matching bills found.';
            }
        }

        return view('revenue.adjustments.index', [
            'filters' => [
                'bill_no' => $billNo,
                'q' => $q,
            ],
            'message' => $message,
            'rows' => $rows,
            'categories' => RevenueCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function refund(Request $request, Revenue $item, RevenueAdjustmentService $service)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string'],
        ]);

        $refundedSoFar = (float) RevenueAdjustment::query()
            ->where('revenue_id', $item->id)
            ->where('type', 'refund')
            ->sum('amount');

        $refundable = max(0.0, (float) $item->amount - $refundedSoFar);
        $amount = (float) $validated['amount'];
        if ($amount > $refundable + 0.0001) {
            return back()->withInput()->withErrors([
                'amount' => 'Refund amount exceeds refundable balance. Available: ' . number_format($refundable, 2),
            ]);
        }

        $userId = $request->user()?->id;

        DB::transaction(function () use ($item, $validated, $amount, $service, $userId) {
            RevenueAdjustment::create([
                'revenue_id' => $item->id,
                'student_id' => $item->student_id,
                'type' => 'refund',
                'amount' => $amount,
                'reason' => $validated['reason'] ?? null,
                'effective_month' => (int) optional($item->paid_at)->format('n'),
                'effective_year' => (int) optional($item->paid_at)->format('Y'),
                'created_by' => $userId,
            ]);

            $service->reverseAllocationsForRefund($item, $amount);
        });

        app(AuditLogger::class)->log(
            'revenue.refund',
            $item,
            'Refund created',
            [
                'bill_no' => $item->bill_no,
                'amount' => $amount,
                'reason' => $validated['reason'] ?? null,
            ]
        );

        return back()->with('status', 'Refund saved successfully.');
    }

    public function waiver(Request $request, Revenue $item)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string'],
        ]);

        $waivedSoFar = (float) RevenueAdjustment::query()
            ->where('revenue_id', $item->id)
            ->where('type', 'waiver')
            ->sum('amount');

        $remaining = max(0.0, (float) $item->amount - $waivedSoFar);
        $amount = (float) $validated['amount'];
        if ($amount > $remaining + 0.0001) {
            return back()->withInput()->withErrors([
                'amount' => 'Waiver amount exceeds remaining allowed for this bill. Available: ' . number_format($remaining, 2),
            ]);
        }

        RevenueAdjustment::create([
            'revenue_id' => $item->id,
            'student_id' => $item->student_id,
            'type' => 'waiver',
            'amount' => $amount,
            'reason' => $validated['reason'] ?? null,
            'effective_month' => (int) optional($item->paid_at)->format('n'),
            'effective_year' => (int) optional($item->paid_at)->format('Y'),
            'created_by' => $request->user()?->id,
        ]);

        app(AuditLogger::class)->log(
            'revenue.waiver',
            $item,
            'Waiver created',
            [
                'bill_no' => $item->bill_no,
                'amount' => $amount,
                'reason' => $validated['reason'] ?? null,
            ]
        );

        return back()->with('status', 'Waiver saved successfully.');
    }

    private function buildRow(Revenue $revenue): array
    {
        $adjustments = $revenue->relationLoaded('adjustments')
            ? $revenue->adjustments
            : RevenueAdjustment::query()->with('creator')->where('revenue_id', $revenue->id)->get();

        $refunds = (float) $adjustments->where('type', 'refund')->sum(fn ($a) => (float) $a->amount);
        $waivers = (float) $adjustments->where('type', 'waiver')->sum(fn ($a) => (float) $a->amount);

        $netCollected = max(0.0, (float) $revenue->amount - $refunds);

        return [
            'revenue' => $revenue,
            'refunds' => $refunds,
            'waivers' => $waivers,
            'net_collected' => $netCollected,
            'adjustments' => $adjustments->sortByDesc('created_at')->values(),
        ];
    }
}
