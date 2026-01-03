<?php

namespace App\Mail;

use App\Models\TeacherSalaryPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TeacherPayslipMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly TeacherSalaryPayment $payment,
        public readonly string $pdfBinary,
        public readonly string $pdfFilename,
    ) {
    }

    public function build(): static
    {
        $schoolName = (string) app('settings')->get('school.name', config('app.name'));

        return $this
            ->subject($schoolName.' - Payslip '.$this->payment->receipt_number)
            ->view('emails.teacher_payslip')
            ->with([
                'payment' => $this->payment,
                'schoolName' => $schoolName,
            ])
            ->attachData($this->pdfBinary, $this->pdfFilename, [
                'mime' => 'application/pdf',
            ]);
    }
}
