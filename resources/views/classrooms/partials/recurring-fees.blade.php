@include('class-fees.partials.scripts')
@php
    $savedTerms = old('exam_terms', $classFeeValues['exam_terms'] ?? null);
    $legacyExamDate = old('exam_fee_start_date', $classFeeValues['exam_fee_start_date'] ?? '');
    $useTerms = $savedTerms !== null || empty($classFeeValues['exam_fee_start_date']);
@endphp
<div class="border-t pt-5 space-y-5" x-data="{ ...termCalendar(@js($savedTerms), @js($legacyExamDate)), enabled: @js($useTerms), feeAmount: @js(old('exam_fee', $classFeeValues['exam_fee'] ?? '')) }">
    <div><h3 class="text-base font-semibold text-gray-900">Exam terms and facility fees</h3><p class="mt-1 text-sm text-gray-600">Three terms per academic year, normally four months each. The fee becomes due on the term’s starting date. Unpaid balances stay due after the term ends.</p></div>
    <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-5 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><x-input-label for="exam_fee" value="Exam fee per term (Rs)" /><x-text-input id="exam_fee" name="exam_fee" type="number" min="0" max="9999999999.99" step="0.01" x-model="feeAmount" class="mt-1 w-full" :value="old('exam_fee', $classFeeValues['exam_fee'] ?? '')" /><x-input-error :messages="$errors->get('exam_fee')" class="mt-2" /></div>
            <div><label class="flex items-center gap-2 mt-6 text-sm font-semibold text-indigo-900"><input type="checkbox" x-model="enabled" @checked($useTerms) @disabled($savedTerms !== null) class="rounded border-gray-300 text-indigo-600"> Use three editable terms</label><p class="mt-1 text-xs text-gray-600">Existing receipts are kept with their corresponding term.</p></div>
        </div>
        <div x-show="enabled" class="space-y-4">
            <p class="text-sm text-indigo-900">Set Term 1’s dates. Terms 2 and 3 fill automatically; each date can then be adjusted.</p>
            @for($i = 0; $i < 3; $i++)
                <fieldset class="grid grid-cols-1 sm:grid-cols-2 gap-4 rounded-lg bg-white p-4 border border-indigo-100">
                    <legend class="px-2 text-sm font-semibold text-indigo-900">Term {{ $i + 1 }}</legend>
                    <div><label for="term-{{ $i }}-start" class="block text-sm font-medium text-gray-700">Starting date</label><input id="term-{{ $i }}-start" type="date" name="exam_terms[{{ $i }}][start]" value="{{ $savedTerms[$i]['start'] ?? ($i === 0 ? $legacyExamDate : '') }}" x-model="terms[{{ $i }}].start" @if($i === 0) @change="firstStartChanged()" @endif :disabled="!enabled || Number(feeAmount) <= 0" :required="enabled && Number(feeAmount) > 0" min="2000-01-01" max="2099-12-31" class="mt-1 w-full rounded-md border-gray-300"></div>
                    <div><label for="term-{{ $i }}-end" class="block text-sm font-medium text-gray-700">Ending date</label><input id="term-{{ $i }}-end" type="date" name="exam_terms[{{ $i }}][end]" value="{{ $savedTerms[$i]['end'] ?? '' }}" x-model="terms[{{ $i }}].end" @if($i === 0) @change="fillRemaining()" @endif :disabled="!enabled || Number(feeAmount) <= 0" :required="enabled && Number(feeAmount) > 0" :min="terms[{{ $i }}].start || '2000-01-01'" max="2099-12-31" class="mt-1 w-full rounded-md border-gray-300"></div>
                </fieldset>
            @endfor
            <button type="button" @click="fillRemaining()" class="text-sm font-semibold text-indigo-700 underline">Reset Terms 2 &amp; 3 from Term 1</button>
            <p class="text-xs text-gray-600">The calendar repeats annually. Before any payments are recorded, you can correct the fee and term dates, including earlier dates. Once payments exist, rate changes start in a future academic year and payment history is preserved.</p>
        </div>
        <div x-show="!enabled" x-cloak><x-input-label for="exam_fee_start_date" value="Legacy exam fee first due date (every 3 months)" /><x-text-input id="exam_fee_start_date" name="exam_fee_start_date" type="date" x-bind:disabled="enabled" x-model="terms[0].start" class="mt-1 w-full" :value="$legacyExamDate" /><x-input-error :messages="$errors->get('exam_fee_start_date')" class="mt-2" /><p class="mt-2 text-xs text-gray-600">Enable three terms above to replace the quarterly calendar. Recorded installments are mapped to the corresponding term in each year.</p></div>
        <template x-if="enabled"><input type="hidden" name="exam_fee_start_date" :value="terms[0].start"></template>
        <x-input-error :messages="$errors->get('exam_terms')" class="mt-2" />
        @foreach($errors->get('exam_terms.*') as $messages)<x-input-error :messages="$messages" class="mt-2" />@endforeach
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><x-input-label for="facility_fee" value="Facility fee (Rs) — once a year" /><x-text-input id="facility_fee" name="facility_fee" type="number" min="0" max="9999999999.99" step="0.01" class="mt-1 w-full" :value="old('facility_fee', $classFeeValues['facility_fee'] ?? '')" /><x-input-error :messages="$errors->get('facility_fee')" class="mt-2" /></div>
        <div><x-input-label for="facility_fee_start_date" value="Facility fee first due date" /><x-text-input id="facility_fee_start_date" name="facility_fee_start_date" type="date" min="2000-01-01" max="2099-12-31" class="mt-1 w-full" :value="old('facility_fee_start_date', $classFeeValues['facility_fee_start_date'] ?? '')" /><x-input-error :messages="$errors->get('facility_fee_start_date')" class="mt-2" /></div>
    </div>
    <p class="text-xs text-gray-500">Before any payments are recorded for a fee, you can freely correct its amount and starting date. Blank or zero removes an unpaid setup; fees with payment history retain earlier installments and receipts. Students joining on or before a term’s ending date owe that term; completed terms before admission do not apply. Annual facility fees apply from the joining year, regardless of the joining month.</p>
</div>
