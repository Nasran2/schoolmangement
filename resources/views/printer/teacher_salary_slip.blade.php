<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Teacher Salary Slip</title>
    <style>
        body { font-family: ui-sans-serif, system-ui; }
        .container { max-width: 640px; margin: 0 auto; }
        .section { margin-top: 16px; }
        .muted { color: #4b5563; }
        .border { border: 1px solid #e5e7eb; padding: 12px; border-radius: 6px; }
    </style>
    <script>window.onload = function() { window.print(); };</script>
    {!! $slipHeader !!}
</head>
<body>
<div class="container">
    <h2>Salary Payment Slip</h2>
    <div class="muted">{{ $schoolName ?? config('app.name') }}</div>

    <div class="section border">
        <div><strong>Teacher:</strong> {{ $payment->teacher?->name }}</div>
        <div><strong>Date:</strong> {{ optional($payment->paid_at)->format('Y-m-d') }}</div>
        <div><strong>Amount:</strong> {{ number_format((float) $payment->amount, 2) }}</div>
        <div><strong>Notes:</strong> {{ $payment->notes }}</div>
    </div>

    <div class="section">
        {!! $slipFooter !!}
    </div>
</div>
</body>
</html>
