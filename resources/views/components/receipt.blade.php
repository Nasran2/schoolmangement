@props(['revenue', 'student', 'category', 'schoolInfo', 'schoolLogoDataUri' => null])

@php
// Simple number to words (en) helper used for the "sum of" line
if (!function_exists('numberToWords')) {
    function numberToWords($number) {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $number = floor((float) $number);
        if ($number == 0) return 'Zero';
        $words = '';
        if ($number >= 100000) {
            $words .= numberToWords(floor($number / 100000)) . ' Lakh ';
            $number %= 100000;
        }
        if ($number >= 1000) {
            $words .= numberToWords(floor($number / 1000)) . ' Thousand ';
            $number %= 1000;
        }
        if ($number >= 100) {
            $words .= $ones[floor($number / 100)] . ' Hundred ';
            $number %= 100;
        }
        if ($number >= 20) {
            $words .= $tens[floor($number / 10)] . ' ';
            $number %= 10;
        }
        if ($number > 0) {
            $words .= $ones[$number] . ' ';
        }
        return trim($words);
    }
}

$paidAt = $revenue->paid_at ?: now();
$year = $paidAt->format('Y');
$monthShort = $paidAt->format('M');
$months = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
$amount = (float) ($revenue->amount ?? 0);
$amountWords = ucwords(numberToWords($amount)) . ' Rupees Only';
// Fee type helpers based on category.payment_type
$paymentType = strtolower($category->payment_type ?? '');
$isMonthly = !empty($category?->interval_months);
$isAdmission = $paymentType === 'admission';
$isFacilities = $paymentType === 'facilities';
$isTerm = in_array($paymentType, ['term','semester','term_fee']);

$schoolName = $schoolInfo['name'] ?? config('app.name');
$schoolAddress = $schoolInfo['address'] ?? '';
$schoolPhone = $schoolInfo['phone'] ?? '';

$refundedAmount = 0.0;
$waivedAmount = 0.0;
try {
    $refundedAmount = (float) \App\Models\RevenueAdjustment::query()
        ->where('revenue_id', $revenue->id)
        ->where('type', 'refund')
        ->sum('amount');
    $waivedAmount = (float) \App\Models\RevenueAdjustment::query()
        ->where('revenue_id', $revenue->id)
        ->where('type', 'waiver')
        ->sum('amount');
} catch (\Exception $e) {
    $refundedAmount = 0.0;
    $waivedAmount = 0.0;
}
$netCollected = max(0.0, $amount - $refundedAmount);
// Months covered by this receipt via allocations
$coveredMonths = [];
if (method_exists($revenue, 'allocations')) {
    foreach ($revenue->allocations as $al) {
        $coveredMonths[] = [
            'month' => (int) $al->month,
            'year' => (int) $al->year,
            'partial' => (bool) $al->is_partial,
            'type' => (string) $al->type,
            'amount' => (float) $al->applied_amount,
        ];
    }
}
// Fallback: if allocations relationship returned empty, try direct query (in case of loading issues)
if (empty($coveredMonths) && $revenue->exists) {
    try {
        $directAllocs = \App\Models\StudentMonthFeeAllocation::where('revenue_id', $revenue->id)->get();
        foreach ($directAllocs as $al) {
            $coveredMonths[] = [
                'month' => (int) $al->month,
                'year' => (int) $al->year,
                'partial' => (bool) $al->is_partial,
                'type' => (string) $al->type,
                'amount' => (float) $al->applied_amount,
            ];
        }
    } catch (\Exception $e) {
        // Ignore errors if table doesn't exist or other DB issues
    }
}

// Compute boxes to mark
$boxed = array_fill(1, 12, false);
foreach ($coveredMonths as $cm) { $boxed[$cm['month']] = true; }
// Monthly fee sum in this receipt (from allocations if present)
$monthlySum = 0.0;
foreach ($coveredMonths as $cm) { $monthlySum += (float) $cm['amount']; }
if ($monthlySum <= 0 && !empty($category?->interval_months)) { $monthlySum = $amount; }

// Determine parent/guardian name
$parentName = $student?->guardian_name;
if (empty($parentName) && $student) {
    $parentName = $student->father_name_with_initial ?: $student->mother_name_with_initial;
}

// Determine address
$studentAddress = $student?->address;
if (empty($studentAddress) && $student) {
    $studentAddress = $student->parent_address;
}
@endphp

<div id="receipt-print" class="bg-white p-8 max-w-3xl mx-auto" style="font-family: 'Courier New', monospace;">
    <div class="flex justify-between items-start">
        <div>
            <div class="flex items-center gap-3">
                @if(!empty($schoolLogoDataUri))
                    <img src="{{ $schoolLogoDataUri }}" alt="Logo" class="h-12 w-12 object-contain" />
                @endif
                <div class="leading-tight">
                    @if($schoolAddress)
                        <div class="text-xl font-extrabold uppercase">{{ $schoolAddress }}</div>
                    @endif
                    <div class="text-xl font-extrabold uppercase">{{ $schoolName }}</div>
                </div>
            </div>
            @if($schoolPhone)
                <p class="text-sm">Tel: {{ $schoolPhone }}</p>
            @endif
        </div>
        <div class="text-right">
            <div class="border border-gray-800 px-3 py-2 inline-block">
                <span class="font-bold mr-2">Receipt No</span>
                <span>{{ $revenue->bill_no ?? 'N/A' }}</span>
            </div>
            <div class="mt-2">
                <span class="font-bold">Date:</span>
                <span class="inline-block border-b border-gray-800 min-w-[140px] text-center">{{ $paidAt->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>

    <div class="mt-4 space-y-2 text-sm">
        <div>
            <span class="font-semibold">Received with thanks from Master/Ms</span>
            <span class="inline-block border-b border-gray-800 min-w-[220px]">{{ $student?->name }}</span>
            <span class="ml-6 font-semibold">Mr/Mrs.Ms</span>
            <span class="inline-block border-b border-gray-800 min-w-[220px]">{{ $parentName }}</span>
        </div>
        <div>
            <span class="inline-block border-b border-gray-800 min-w-[300px]">{{ $studentAddress }}</span>
            <span class="ml-3">(Address)</span>
        </div>
        <div>
            <span class="mr-2">the sum</span>
            <span class="inline-block border-b border-gray-800 min-w-[120px]">Rs {{ number_format($amount,2) }}</span>
            <span class="mx-2">of</span>
            <span class="inline-block border-b border-gray-800 min-w-[360px]">{{ $amountWords }}</span>
        </div>
        @if($refundedAmount > 0 || $waivedAmount > 0)
            <div class="text-xs">
                @if($refundedAmount > 0)
                    <div><span class="font-semibold">Refunded:</span> Rs {{ number_format($refundedAmount,2) }} &nbsp; <span class="font-semibold">Net Collected:</span> Rs {{ number_format($netCollected,2) }}</div>
                @endif
                @if($waivedAmount > 0)
                    <div><span class="font-semibold">Waived:</span> Rs {{ number_format($waivedAmount,2) }}</div>
                @endif
            </div>
        @endif
        <div>
            <span class="mr-2">Being payment for</span>
            <span class="inline-block border-b border-gray-800 min-w-[360px]">{{ $category->name ?? 'Fees' }}</span>
        </div>
    </div>

    @if($isMonthly)
        <div class="mt-4">
            <div class="flex items-center gap-2 text-xs">
                @foreach($months as $idx => $m)
                    @php $monthNum = $idx+1; $active = !empty($boxed[$monthNum]); @endphp
                    @if($active)
                        <div class="flex items-center">
                            <div class="w-5 h-5 border border-gray-800 text-center leading-5 bg-gray-900 text-white">X</div>
                            <div class="ml-1 mr-3">{{ $m }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
            @php $hasPartial = collect($coveredMonths)->contains(fn($cm) => $cm['partial']); @endphp
            @if($hasPartial)
                <div class="mt-1 text-xs text-gray-700">Note: Partial payments included.</div>
            @endif
        </div>
    @endif

    @if($isTerm)
        <div class="mt-4">
            <div class="flex items-center gap-2 text-xs">
                <span class="ml-4 font-semibold">Term Fee</span>
                <div class="w-6 h-6 border border-gray-800 ml-2"></div>
                <div class="w-6 h-6 border border-gray-800 ml-2"></div>
                <div class="w-6 h-6 border border-gray-800 ml-2"></div>
                <span class="ml-3">{{ substr($year,0,2) }}<span class="inline-block border-b border-gray-800 min-w-[30px] ml-1">{{ substr($year,2,2) }}</span></span>
            </div>
        </div>
    @endif

    <div class="mt-4 text-sm">
        <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
                @if($isAdmission)
                    <div class="flex justify-between"><span>I. Admission fee :</span><span class="inline-block min-w-[140px] text-right">Rs {{ number_format($amount,2) }}</span></div>
                @endif
                @if($isMonthly)
                    <div class="flex justify-between"><span>{{ $isAdmission ? 'II.' : 'I.' }} Monthly fee :</span><span class="inline-block min-w-[140px] text-right">Rs {{ number_format($monthlySum,2) }}</span></div>
                @endif
                @if($isFacilities)
                    <div class="flex justify-between"><span>{{ ($isAdmission || $isMonthly) ? 'III.' : 'I.' }} Facilities fee :</span><span class="inline-block min-w-[140px] text-right">Rs {{ number_format($amount,2) }}</span></div>
                @endif
                @if(!$isAdmission && !$isMonthly && !$isFacilities)
                    <div class="flex justify-between"><span>{{ $category->name }} :</span><span class="inline-block min-w-[140px] text-right">Rs {{ number_format($amount,2) }}</span></div>
                @endif
                <div class="flex justify-between font-semibold"><span>Total :</span><span class="inline-block min-w-[140px] text-right">Rs {{ number_format($amount,2) }}</span></div>
                @if($refundedAmount > 0)
                    <div class="flex justify-between"><span>Refunded :</span><span class="inline-block min-w-[140px] text-right">Rs {{ number_format($refundedAmount,2) }}</span></div>
                    <div class="flex justify-between font-semibold"><span>Net Collected :</span><span class="inline-block min-w-[140px] text-right">Rs {{ number_format($netCollected,2) }}</span></div>
                @endif
                @if($waivedAmount > 0)
                    <div class="flex justify-between"><span>Waived :</span><span class="inline-block min-w-[140px] text-right">Rs {{ number_format($waivedAmount,2) }}</span></div>
                @endif
            </div>
            <div class="space-y-2">
                <div class="flex items-center gap-4"><span>Grade</span><span class="inline-block border-b border-gray-800 min-w-[120px]">{{ ($student?->alumni ?? false) ? 'Alumni' : ($student?->classRoom?->name ?? $student?->class) }}</span></div>
                <div class="flex items-center gap-4"><span>Std.reg.No.</span><span class="inline-block border-b border-gray-800 min-w-[120px]">{{ $student?->admission_number }}</span></div>
                <div class="flex items-center gap-6 mt-2">
                    <div class="flex items-center gap-2"><span>Junior</span><div class="w-6 h-6 border border-gray-800"></div></div>
                    <div class="flex items-center gap-2 ml-6"><span>Senior</span><div class="w-6 h-6 border border-gray-800"></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6">
        @if($isMonthly && !empty($coveredMonths))
            <div class="mb-4 text-sm">
                <p class="font-semibold">Months covered in this receipt:</p>
                <ul class="list-disc ml-5">
                    @foreach($coveredMonths as $cm)
                        <li>
                            {{ \Carbon\Carbon::createFromDate($cm['year'],$cm['month'],1)->format('F Y') }} – Rs {{ number_format($cm['amount'],2) }} 
                            ({{ ucfirst($cm['type']) }}
                            @if(!empty($cm['partial']))
                                , partial
                            @endif
                            )
                        </li>
                    @endforeach
                </ul>
                @php
                    $dueList = collect($coveredMonths)->where('type','due')->map(fn($cm)=>\Carbon\Carbon::createFromDate($cm['year'],$cm['month'],1)->format('F Y'))->all();
                    $advList = collect($coveredMonths)->where('type','advance')->map(fn($cm)=>\Carbon\Carbon::createFromDate($cm['year'],$cm['month'],1)->format('F Y'))->all();
                @endphp
                @if(!empty($dueList) || !empty($advList))
                    <p class="mt-2 text-xs text-gray-700">Includes settlement of outstanding fees for {{ empty($dueList) ? '—' : implode(', ', $dueList) }}@if(!empty($advList)) and advance payment for {{ implode(', ', $advList) }}@endif.</p>
                @endif
            </div>
        @endif
    </div>

    <div class="mt-6 flex justify-end">
        <div class="text-center">
            <div class="border-t border-gray-800 pt-1 w-56">
                <p class="text-xs">Authorized Signature | Cashier</p>
            </div>
        </div>
    </div>

    <div class="text-center mt-6 text-xs text-gray-600">This is a computer-generated receipt</div>
</div>

<style media="print">
    @page { size: A4; margin: 10mm; }
    body * { visibility: hidden; }
    #receipt-print, #receipt-print * { visibility: visible; }
    #receipt-print { position: absolute; left: 0; top: 0; width: 100%; }
</style>
