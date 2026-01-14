<?php

namespace App\Http\Controllers;

use App\Models\Seminar;
use Illuminate\Http\Request;

class SeminarReportController extends Controller
{
    public function due()
    {
        $seminars = Seminar::query()
            ->with(['students' => function($q) { $q->where('present', true)->where('paid', false)->with('student'); }])
            ->latest('date')
            ->paginate(20);

        return view('seminars.reports.due', compact('seminars'));
    }
}
