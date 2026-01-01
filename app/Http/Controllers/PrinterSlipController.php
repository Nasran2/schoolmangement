<?php

namespace App\Http\Controllers;

use App\Models\Revenue;
use App\Models\TeacherSalaryPayment;
use Illuminate\View\View;

class PrinterSlipController extends Controller
{
    public function revenue(Revenue $item): View
    {
        return view('printer.revenue_slip', [
            'item' => $item->load(['category', 'student']),
            'slipHeader' => app('settings')->get('printer.slip.header', ''),
            'slipFooter' => app('settings')->get('printer.slip.footer', ''),
        ]);
    }

    public function teacher(TeacherSalaryPayment $payment): View
    {
        return view('printer.teacher_salary_slip', [
            'payment' => $payment->load(['teacher']),
            'slipHeader' => app('settings')->get('printer.slip.header', ''),
            'slipFooter' => app('settings')->get('printer.slip.footer', ''),
        ]);
    }
}
