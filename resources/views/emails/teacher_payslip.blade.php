<p>Dear {{ $payment->teacher?->name ?? 'Teacher' }},</p>

<p>Please find attached your salary payslip for {{ $payment->payment_month }}.</p>

<p>Regards,<br>
{{ $schoolName }}</p>
