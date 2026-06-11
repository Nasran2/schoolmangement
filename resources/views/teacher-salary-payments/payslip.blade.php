<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Staff Salary Payslip</title>
	<style>
		body { font-family: Arial, sans-serif; line-height: 1.25; color: #111827; margin: 0; padding: 5px; }
		.container { max-width: 100%; margin: 0 auto; border: 1.5px solid #cbd5e1; padding: 10px; background: #fff; }
		.top { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 12px; margin-bottom: 5px; }
		.brand { font-size: 12px; font-weight: 700; color: #111827; }
		.brand-sub { font-size: 10px; color: #6b7280; }
		.logo { width: 55px; height: 55px; object-fit: contain; }
		.title { text-align: center; font-weight: 700; font-size: 13px; color: #111827; }
		.bar { background: #e5e7eb; border: 1px solid #d1d5db; color: #111827; font-weight: 700; font-size: 10px; padding: 4px 6px; text-align: center; margin-top: 5px; }
		.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 5px; }
		.box { border: 1px solid #d1d5db; }
		.box-header { background: #e5e7eb; padding: 4px 6px; font-weight: 700; font-size: 9px; }
		.box-body { padding: 6px; font-size: 10px; }
		.row { display: grid; grid-template-columns: 110px 1fr; gap: 5px; margin-bottom: 3px; }
		.muted { color: #374151; }
		.amount { text-align: right; }
		.hr { margin: 5px 0; border-top: 1px solid #d1d5db; }
		.net { background: #d1fae5; border: 1px solid #a7f3d0; padding: 7px; font-weight: 700; font-size: 12px; margin-top: 5px; text-align: center; }
		.method { margin-top: 5px; font-size: 10px; color: #374151; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
		.checkbox { display: inline-block; width: 9px; height: 9px; border: 1px solid #475569; margin-right: 5px; vertical-align: middle; }
		.checkbox.checked { background: #334155; }
		.line { border-bottom: 1px solid #d1d5db; display: inline-block; width: 160px; vertical-align: middle; }
		.sig { text-align: right; }
		.sig-name { font-size: 9px; color: #374151; margin-top: 3px; }
		.footer { margin-top: 4px; padding-top: 4px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 8px; color: #6b7280; }
		.words { font-size: 9px; color: #0f172a; margin-top: 5px; }
		@page { size: A4; margin: 10mm; }
		@media print { body { padding: 0; margin: 0; } .container { border: none; box-shadow: none; max-width: 100%; padding: 8px; page-break-inside: avoid; } }
	</style>
</head>
<body>
	<div class="container">
		@php
			$settings = app(\App\Services\SettingsService::class);
			$schoolName = $settings->get('school.name', config('app.name'));
			$schoolAddress = $settings->get('school.address', '');
			$logoPath = $settings->get('school.logo');
			$logoUrl = $logoPath ? asset('storage/' . $logoPath) : null;
		@endphp
		<div class="top">
			<div>
				<div class="brand">{{ $schoolName }}</div>
				@if($schoolAddress)
				<div class="brand-sub">{{ $schoolAddress }}</div>
				@endif
			</div>
			<div style="text-align: center;">
				@if($logoUrl)
					<img src="{{ $logoUrl }}" alt="Logo" class="logo" />
				@endif
			</div>
			<div class="title">STAFF SALARY PAYSLIP</div>
		</div>
		<div class="bar">EARNINGS</div>

		@php
			$svc = app(\App\Services\SettingsService::class);
			$base = (float) ($payment->base_salary ?? 0);
			$deductions = collect($payment->deductions ?? []);
			$epfEntry = $deductions->first(fn($d) => strtolower($d['reason'] ?? '') === 'epf');
			$epfAmount = $payment->employee_epf_amount !== null
				? (float) $payment->employee_epf_amount
				: ($epfEntry ? (float) ($epfEntry['amount'] ?? 0) : 0.0);
			$advanceSettledTotal = (float) $payment->advanceSettlements->sum('amount');
			$advanceEntryTotal = (float) $deductions
				->filter(fn($d) => strtolower($d['reason'] ?? '') === 'advance adjustment')
				->sum('amount');
			$advanceTotal = $advanceSettledTotal > 0 ? $advanceSettledTotal : $advanceEntryTotal;
			$otherDeductions = $deductions->filter(fn($d) => ! in_array(strtolower($d['reason'] ?? ''), ['epf', 'advance adjustment'], true));
			$otherTotal = (float) $otherDeductions->sum('amount');
			$totalDeductions = $epfAmount + $otherTotal + $advanceTotal;
			$netAmount = $base - $totalDeductions;
			$periodText = $payment->payment_month;
			try {
				$dt = \Carbon\Carbon::parse($payment->payment_month . '-01');
				$periodText = $dt->startOfMonth()->format('d/m/Y') . ' - ' . $dt->endOfMonth()->format('d/m/Y');
			} catch (\Exception $e) {}
		@endphp

		<div class="grid">
			<div class="box">
				<div class="box-header">EMPLOYEE DETAILS</div>
				<div class="box-body">
					<div class="row"><div class="muted">Employee</div><div>{{ $payment->teacher->name }}</div></div>
					<div class="row"><div class="muted">Employee ID</div><div>{{ $payment->teacher->id }}</div></div>
					<div class="row"><div class="muted">Designation</div><div>Teacher</div></div>
					<div class="row"><div class="muted">Total Salary</div><div class="amount">LKR {{ number_format($payment->base_salary, 2) }}</div></div>
					<div class="row"><div class="muted">Pay Period</div><div>{{ $periodText }}</div></div>
					<div class="row"><div class="muted">Date Issued</div><div>{{ $payment->paid_at->format('d/m/Y') }}</div></div>
				</div>
			</div>
			<div class="box">
				<div class="box-header">DEDUCTIONS</div>
				<div class="box-body">
					<div class="row"><div>Employee EPF</div><div class="amount">LKR {{ number_format($epfAmount, 2) }}</div></div>
					@if($advanceTotal > 0)
					<div class="row"><div>Advance Already Paid</div><div class="amount">LKR {{ number_format($advanceTotal, 2) }}</div></div>
					@endif
					@if($otherTotal > 0)
					<div class="row"><div>Other Deductions</div><div class="amount">LKR {{ number_format($otherTotal, 2) }}</div></div>
					@endif
					<div class="hr"></div>
					<div class="row" style="font-weight:700;"><div>Total Deductions</div><div class="amount">LKR {{ number_format($totalDeductions, 2) }}</div></div>
				</div>
			</div>
		</div>

		<div class="net">PAID THIS DATE: LKR {{ number_format($netAmount, 2) }} &nbsp; | &nbsp; TOTAL SALARY SETTLED: LKR {{ number_format($base, 2) }}</div>

		@php
			$n = (int) round($netAmount);
			function number_to_words($num) {
				$ones = ["","One","Two","Three","Four","Five","Six","Seven","Eight","Nine","Ten","Eleven","Twelve","Thirteen","Fourteen","Fifteen","Sixteen","Seventeen","Eighteen","Nineteen"];
				$tens = ["","","Twenty","Thirty","Forty","Fifty","Sixty","Seventy","Eighty","Ninety"];
				if ($num == 0) return "Zero";
				$words = "";
				$units = [[10000000,"Crore"],[100000,"Lakh"],[1000,"Thousand"],[100,"Hundred"],[1,""]];
				foreach ($units as [$value,$name]) {
					if ($num >= $value) {
						$count = intdiv($num,$value);
						$num = $num % $value;
						if ($count >= 20) {
							$words .= " " . $tens[intdiv($count,10)] . " " . $ones[$count%10] . ($name?" $name":"");
						} else {
							$words .= " " . $ones[$count] . ($name?" $name":"");
						}
					}
				}
				return trim($words);
			}
			$amountWords = number_to_words($n) . ' Rupees and ' . sprintf('%.0f', ($netAmount - floor($netAmount)) * 100) . ' Cents';
		@endphp
		<div class="words">Paid This Date in Words: <strong>{{ $amountWords }}</strong></div>

		@php $method = strtolower((string)($payment->payment_method ?? '')); @endphp
		<div class="method">
			<div>
				<div style="font-weight:700;">PAYMENT METHOD</div>
				<div style="margin-top:4px;">
					<span class="checkbox {{ $method === 'bank' ? 'checked' : '' }}"></span> Bank
					&nbsp;&nbsp; <span class="checkbox {{ $method === 'cash' ? 'checked' : '' }}"></span> Cash
					&nbsp;&nbsp; <span class="checkbox {{ $method === 'cheque' ? 'checked' : '' }}"></span> Cheque
				</div>
				@if(in_array($method, ['bank','cheque']))
				<div style="margin-top:6px;">Bank / A/C No : 
					<span>
						{{ trim(($payment->bank_name ?? '').(($payment->bank_branch ?? '') ? ' - '.$payment->bank_branch : '')) }}
						{{ $payment->bank_account_no ? ' / '.$payment->bank_account_no : '' }}
					</span>
				</div>
				@else
				<div style="margin-top:6px;">Bank / A/C No : <span class="line"></span></div>
				@endif
			</div>
			<div class="sig">
				<div class="line"></div>
				<div style="font-weight:700; font-size:11px; margin-top:4px;">AUTHORIZED SIGNATURE</div>
				<div class="sig-name">{{ $payment->creator->name ?? '' }}</div>
			</div>
		</div>

		<div class="footer">Generated on {{ now()->format('d M Y') }} • Payslip #{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
	</div>
</body>
</html>
