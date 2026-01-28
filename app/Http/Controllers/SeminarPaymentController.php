<?php

namespace App\Http\Controllers;

use App\Models\Seminar;
use App\Models\SeminarStudent;
use Illuminate\Http\Request;

class SeminarPaymentController extends Controller
{
    public function index(Seminar $seminar)
    {
        $seminar->syncEnrollmentsFromClassroomsIfEmpty();

        $enrollments = $seminar->students()->with('student')->orderByDesc('id')->paginate(100);
        return view('seminars.payments', compact('seminar','enrollments'));
    }

    public function updateAttendancePayment(Request $request, Seminar $seminar)
    {
        $data = $request->validate([
            'items' => ['required','array'],
            'items.*.id' => ['required','integer','exists:seminar_students,id'],
            'items.*.present' => ['nullable','boolean'],
            'items.*.paid' => ['nullable','boolean'],
        ]);

        foreach ($data['items'] as $item) {
            $row = SeminarStudent::where('seminar_id', $seminar->id)->where('id', $item['id'])->first();
            if (!$row) continue;
            $row->present = (bool)($item['present'] ?? false);
            $row->paid = (bool)($item['paid'] ?? false);
            $row->paid_at = $row->paid ? now() : null;
            $row->save();
        }

        return back()->with('status', 'Attendance & payments updated');
    }
}
