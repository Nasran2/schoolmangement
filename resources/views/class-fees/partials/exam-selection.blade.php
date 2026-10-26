@if($studentList ?? false)
    <div class="fee-register-term-row" aria-label="Selected fees">
        @if($type === 'exam')
            <strong class="fee-register-term-caption">Fees</strong>
            @forelse($examOptions as $examDate)
                @php($shortLabel = preg_match('/^Term (\d+)/', $examLabels[$examDate], $termMatch) ? 'Term '.$termMatch[1].' · '.substr($examDate, 0, 4) : \Carbon\Carbon::parse($examDate)->format('d M Y'))
                <label class="fee-register-term-chip" title="{{ $examLabels[$examDate] }}">
                    <input type="checkbox" name="exam_dates[]" value="{{ $examDate }}" aria-label="{{ $examLabels[$examDate] }}" @checked(in_array($examDate, $selectedExamDates, true))>
                    {{ $shortLabel }}
                </label>
            @empty
                <span class="fee-register-term-caption">No exam terms scheduled.</span>
            @endforelse
            <input type="hidden" name="include_facility" value="0">
            <label class="fee-register-term-chip"><input type="checkbox" name="include_facility" value="1" @checked($includeFacility)> Include facility fee</label>
            <button type="button" class="fee-register-term-link" data-exam-select="all">Select all exams</button>
            <button type="button" class="fee-register-term-link" data-exam-select="none">Clear exams</button>
        @else
            <input type="hidden" name="include_facility" value="1">
            <span class="fee-register-term-caption">Facility fees for the selected period.</span>
        @endif
    </div>
@else
                @if($type === 'exam')
                    <fieldset>
                        <legend class="text-sm font-semibold text-gray-800 mb-2">Exam fee installments</legend>
                        <div class="flex flex-wrap items-center gap-3 mb-3">
                            <button type="button" class="text-xs font-semibold text-indigo-700 underline" @if($studentList ?? false) data-exam-select="all" @else @click="selectedExamDates = allExamDates.slice()" @endif>Select all exams</button>
                            <button type="button" class="text-xs font-semibold text-gray-600 underline" @if($studentList ?? false) data-exam-select="none" @else @click="selectedExamDates = []" @endif>Clear exams</button>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            @forelse($examOptions as $examDate)
                                <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                    <input type="checkbox" name="exam_dates[]" value="{{ $examDate }}" @if(!($studentList ?? false)) x-model="selectedExamDates" @endif @checked(in_array($examDate, $selectedExamDates, true)) class="rounded border-gray-300 text-indigo-600">
                                    {{ $examLabels[$examDate] }}
                                </label>
                            @empty
                                <p class="text-sm text-gray-500">No exam installments are scheduled for this class and period.</p>
                            @endforelse
                        </div>
                    </fieldset>
                    <input type="hidden" name="include_facility" value="0">
                    <label class="inline-flex items-center gap-2 rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-800">
                        <input type="checkbox" name="include_facility" value="1" @checked($includeFacility) class="rounded border-gray-300 text-indigo-600">
                        Include facility fee
                    </label>
                    <p class="text-xs text-gray-500">All exam dates and facility fees are selected by default. The list, totals, payment status, and PDF use only the fees you select. Changing the class or year selects all of its exam dates again.</p>
                @else
                    <input type="hidden" name="include_facility" value="1">
                    <p class="text-sm text-gray-600">Facility fee installments for the selected period are included.</p>
                @endif

@endif
