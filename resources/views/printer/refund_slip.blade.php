<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Refund Slip</title>
    <style>
        @page { size: A5 landscape; margin: 0; }
        @media print {
            body { -webkit-print-color-adjust: exact; }
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>window.onload = function() { window.print(); };</script>
    {!! $slipHeader !!}
</head>
<body class="bg-white font-sans text-gray-900 antialiased p-8">
    <div class="max-w-3xl mx-auto border-2 border-gray-800 p-6 relative print:border-0 print:p-0 print:max-w-none">
        
        <!-- Header -->
        <div class="text-center mb-6 border-b-2 border-dashed border-gray-800 pb-4">
            <div class="text-2xl font-bold uppercase mb-1">{{ $schoolName ?? config('app.name') }}</div>
            <div class="text-sm text-gray-600">{{ app('settings')->get('school.address', 'School Address') }}</div>
            <div class="text-sm text-gray-600">{{ app('settings')->get('school.phone', '') }}</div>
            <div class="inline-block mt-3 border border-gray-800 px-4 py-1 text-lg font-bold uppercase">Refund Slip</div>
        </div>

        @php
            $item = $adjustment->revenue;
            $refundedSoFar = 0.0;
            if ($item) {
                try {
                    $refundedSoFar = (float) \App\Models\RevenueAdjustment::query()
                        ->where('revenue_id', $item->id)
                        ->where('type', 'refund')
                        ->sum('amount');
                } catch (\Exception $e) {
                    $refundedSoFar = 0.0;
                }
            }
            $originalAmount = (float) ($item?->amount ?? 0);
            $netCollected = max(0, $originalAmount - $refundedSoFar);
        @endphp

        <!-- Meta Info -->
        <div class="flex justify-between mb-6 text-sm">
            <div>
                <div class="mb-1"><span class="font-bold w-32 inline-block">Refund Date:</span> <span>{{ optional($adjustment->created_at)->format('Y-m-d H:i') }}</span></div>
                <div><span class="font-bold w-32 inline-block">Processed By:</span> <span>{{ $adjustment->creator?->name ?? '-' }}</span></div>
            </div>
            <div class="text-right">
                <div><span class="font-bold">Ref ID:</span> <span>#{{ $adjustment->id }}</span></div>
            </div>
        </div>

        <!-- Student & Bill Details -->
        <div class="mb-2 font-bold uppercase underline decoration-gray-400 underline-offset-4">Student & Bill Details</div>
        
        <table class="w-full border-collapse border border-gray-800 mb-6 text-sm">
            <tr>
                <th class="border border-gray-800 bg-gray-100 p-2 text-left w-1/5">Bill No</th>
                <td class="border border-gray-800 p-2 w-[30%]">{{ $item?->bill_no ?? '-' }}</td>
                <th class="border border-gray-800 bg-gray-100 p-2 text-left w-1/5">Admission No</th>
                <td class="border border-gray-800 p-2 w-[30%]">{{ $item->student->admission_number ?? '-' }}</td>
            </tr>
            <tr>
                <th class="border border-gray-800 bg-gray-100 p-2 text-left">Student Name</th>
                <td class="border border-gray-800 p-2" colspan="3">{{ $item->student->name ?? '-' }}</td>
            </tr>
            <tr>
                <th class="border border-gray-800 bg-gray-100 p-2 text-left">Class / Year</th>
                <td class="border border-gray-800 p-2">{{ $item->student->classRoom?->name ?? $item->student->class ?? '-' }} / {{ $item->student->year ?? '-' }}</td>
                <th class="border border-gray-800 bg-gray-100 p-2 text-left">Category</th>
                <td class="border border-gray-800 p-2">{{ $item?->category?->name ?? '-' }}</td>
            </tr>
        </table>

        <!-- Refund Information -->
        <div class="mb-2 font-bold uppercase underline decoration-gray-400 underline-offset-4">Refund Information</div>

        <table class="w-full border-collapse border border-gray-800 mb-6 text-sm">
            <thead>
                <tr>
                    <th class="border border-gray-800 bg-gray-100 p-2 text-left">Description</th>
                    <th class="border border-gray-800 bg-gray-100 p-2 text-right">Amount (Rs)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border border-gray-800 p-2">
                        <strong>Refund Issued</strong><br>
                        <small class="text-gray-600">Reason: {{ $adjustment->reason ?: 'No reason provided' }}</small>
                    </td>
                    <td class="border border-gray-800 p-2 text-right text-lg font-bold">
                        {{ number_format((float) $adjustment->amount, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Summary -->
        <div class="mt-6 border-t border-dashed border-gray-800 pt-4">
            <div class="flex justify-between items-end">
                <div class="text-sm">
                    <div class="mb-1"><span class="font-bold">Original Bill Amount:</span> Rs {{ number_format($originalAmount, 2) }}</div>
                    <div><span class="font-bold">Total Refunded:</span> Rs {{ number_format($refundedSoFar, 2) }}</div>
                </div>
                <div class="text-right">
                    <div class="text-lg font-bold">
                        Net Collected: Rs {{ number_format($netCollected, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Signatures -->
        <div class="mt-12 flex justify-between items-end">
            <div class="border-t border-gray-800 w-48 text-center pt-2 text-xs">
                Prepared By
            </div>
            <div class="border-t border-gray-800 w-48 text-center pt-2 text-xs">
                Authorized Signature
            </div>
            <div class="border-t border-gray-800 w-48 text-center pt-2 text-xs">
                Receiver Signature
            </div>
        </div>

        <div class="text-[10px] text-center mt-8 text-gray-500">
            This is a computer-generated document. | Printed on {{ now()->format('Y-m-d H:i:s') }}
        </div>

        <div class="mt-4">
            {!! $slipFooter !!}
        </div>
    </div>
</body>
</html>
