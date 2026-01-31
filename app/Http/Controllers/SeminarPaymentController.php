<?php

namespace App\Http\Controllers;

use App\Models\Seminar;
use App\Models\SeminarStudent;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Services\Billing\BillNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SeminarPaymentController extends Controller
{
    private function seminarRevenueCategory(): RevenueCategory
    {
        return RevenueCategory::firstOrCreate(
            ['name' => 'Seminar Fee'],
            [
                'payment_type' => 'seminar',
                'description' => 'Seminar student fee collections',
                'active' => true,
            ]
        );
    }

    public function index(Seminar $seminar)
    {
        $seminar->syncEnrollmentsFromClassroomsIfEmpty();

        $enrollments = $seminar->students()->with('student')->orderByDesc('id')->paginate(100);
        return view('seminars.payments', compact('seminar','enrollments'));
    }

    public function updateAttendancePayment(Request $request, Seminar $seminar, BillNumberService $billNumbers)
    {
        $data = $request->validate([
            'items' => ['required','array'],
            'items.*.id' => ['required','integer','exists:seminar_students,id'],
            'items.*.present' => ['nullable','boolean'],
            'items.*.paid' => ['nullable','boolean'],
        ]);

        $category = $this->seminarRevenueCategory();

        DB::transaction(function () use ($data, $seminar, $billNumbers, $category) {
            foreach ($data['items'] as $item) {
                /** @var SeminarStudent|null $row */
                $row = SeminarStudent::query()
                    ->where('seminar_id', $seminar->id)
                    ->where('id', $item['id'])
                    ->lockForUpdate()
                    ->first();

                if (! $row) {
                    continue;
                }

                $row->present = (bool) ($item['present'] ?? false);

                $paidRequested = (bool) ($item['paid'] ?? false);
                if ($paidRequested) {
                    $row->paid = true;
                    $row->paid_at = $row->paid_at ?: now();
                    $row->amount = $row->amount ?? $seminar->fee_per_student;

                    $paidDate = $row->paid_at?->toDateString() ?? now()->toDateString();
                    $amount = (float) ($row->amount ?? $seminar->fee_per_student);

                    if ($row->revenue_id) {
                        $revenue = Revenue::query()->lockForUpdate()->find($row->revenue_id);
                        if ($revenue) {
                            $revenue->update([
                                'revenue_category_id' => $category->id,
                                'student_id' => $row->student_id,
                                'amount' => $amount,
                                'paid_at' => $paidDate,
                            ]);
                        } else {
                            $row->revenue_id = null;
                        }
                    }

                    if (! $row->revenue_id) {
                        $billNo = $billNumbers->nextRevenueBillNumber() ?: null;

                        $revenue = Revenue::create([
                            'bill_no' => $billNo,
                            'revenue_category_id' => $category->id,
                            'student_id' => $row->student_id,
                            'amount' => $amount,
                            'paid_at' => $paidDate,
                            'notes' => "Seminar {$seminar->name} fee",
                            'created_by' => Auth::id(),
                        ]);

                        $row->revenue_id = $revenue->id;
                    }
                } else {
                    $row->paid = false;
                    $row->paid_at = null;

                    if ($row->revenue_id) {
                        $revenue = Revenue::query()->find($row->revenue_id);
                        if ($revenue) {
                            $revenue->delete();
                        }
                        $row->revenue_id = null;
                    }
                }

                $row->save();
            }
        });

        return back()->with('status', 'Attendance & payments updated');
    }
}
