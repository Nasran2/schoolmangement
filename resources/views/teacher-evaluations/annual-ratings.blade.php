<section class="evaluation-sheet overflow-hidden rounded-xl border bg-white shadow-sm">
    <div class="border-b p-6">
        <h3 class="evaluation-section-label">Annual performance ratings</h3>
        <p class="mt-1 text-sm text-gray-600">One supervisor is enough. Use the second and third columns when more supervisors reviewed the teacher.</p>
        <div class="mt-5 grid gap-4 md:grid-cols-3">
            @foreach([1, 2, 3] as $supervisor)
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="flex items-center justify-between gap-2"><label for="supervisor_{{ $supervisor }}" class="text-sm font-semibold">Supervisor {{ $supervisor }}</label><span class="text-xs text-gray-500">Optional name</span></div>
                    <input id="supervisor_{{ $supervisor }}" name="supervisors[{{ $supervisor - 1 }}]" value="{{ old('supervisors.'.($supervisor - 1), $evaluation->supervisors[$supervisor - 1] ?? ($supervisor === 1 ? $evaluation->evaluator : '')) }}" maxlength="191" placeholder="Reviewer name" class="mt-2 w-full rounded-md border-gray-300 text-sm">
                    <div class="mt-3 flex items-center justify-between gap-2"><span class="text-xs text-gray-500" x-text="ratedCount({{ $supervisor }}) + ' of ' + Object.keys(weights).length + ' ratings'"></span><button type="button" class="text-xs font-medium text-indigo-600 hover:underline" @click="clearSupervisor({{ $supervisor }})">Clear ratings</button></div>
                    <p class="mt-1 font-semibold text-indigo-700" x-text="supervisorScore({{ $supervisor }}) !== null ? supervisorScore({{ $supervisor }}).toFixed(2) + ' / 100' : 'Not completed'"></p>
                </div>
            @endforeach
        </div>
        <div class="mt-4 rounded-lg bg-indigo-50 p-4 text-indigo-900" aria-live="polite"><p class="text-xs font-semibold uppercase tracking-wide">Overall average</p><p class="mt-1 text-xl font-bold" x-text="overallScore()"></p><p class="mt-1 text-xs" x-text="completedCount() + ' completed supervisor review(s). Blank or partial columns are excluded.'"></p></div>
        <p class="mt-4 text-xs text-gray-500">5 Outstanding · 4 Exceeds expectations · 3 Meets expectations · 2 Needs improvement · 1 Unsatisfactory. Missing ratings may be left blank. Saving shows a warning so you can review the missing fields or save anyway.</p>
    </div>
    <p class="evaluation-scroll-hint">Enter ratings in the table below. Scroll sideways to see every supervisor and the remarks column.</p>
    <div class="evaluation-grid-scroll" role="region" aria-label="Annual appraisal worksheet" tabindex="0">
        <table class="evaluation-entry-table annual-entry-table">
            <thead><tr><th style="width:45px">No.</th><th style="width:190px">Performance area</th><th style="width:260px">Expected standard / evidence</th><th style="width:60px">Weight %</th>@foreach([1, 2, 3] as $supervisor)<th style="width:115px">Supervisor {{ $supervisor }}<br>Rating</th><th style="width:70px">Weighted<br>score</th>@endforeach<th style="width:230px">Evidence / remarks</th></tr></thead>
            <tbody>
                @foreach($criteria as $criterion)
                    <tr><td class="evaluation-row-number">{{ $criterion['id'] }}</td><th scope="row">{{ $criterion['area'] }}</th><td class="evaluation-standard">{{ $criterion['standard'] }}</td><td class="evaluation-weight">{{ $criterion['weight'] }}%</td>
                        @foreach([1, 2, 3] as $supervisor)
                            @php
                                $inputId = $supervisor === 1 ? 'rating_'.$criterion['id'] : 'rating_'.$supervisor.'_'.$criterion['id'];
                                $inputName = $supervisor === 1 ? 'ratings['.$criterion['id'].']' : 'additional_ratings['.$supervisor.']['.$criterion['id'].']';
                                $model = $supervisor === 1 ? "ratings['".$criterion['id']."']" : "additionalRatings[".$supervisor."]['".$criterion['id']."']";
                                $selectedRating = $supervisor === 1 ? ($ratings[$criterion['id']] ?? '') : ($additionalRatings[$supervisor][$criterion['id']] ?? '');
                            @endphp
                            <td class="evaluation-rating"><label for="{{ $inputId }}" class="sr-only">Supervisor {{ $supervisor }} rating for {{ $criterion['area'] }}</label><select id="{{ $inputId }}" name="{{ $inputName }}" x-model="{{ $model }}"><option value="">Not entered</option>@foreach([5 => 'Outstanding', 4 => 'Exceeds expectations', 3 => 'Meets expectations', 2 => 'Needs improvement', 1 => 'Unsatisfactory'] as $rating => $label)<option value="{{ $rating }}" @selected((string)$selectedRating === (string)$rating)>{{ $rating }} - {{ $label }}</option>@endforeach</select></td>
                            <td class="evaluation-calculated"><output for="{{ $inputId }}" aria-label="Supervisor {{ $supervisor }} weighted score for {{ $criterion['area'] }}" x-text="{{ $model }} ? ({{ $criterion['weight'] }} * Number({{ $model }}) / 5).toFixed(2) : '—'"></output></td>
                        @endforeach
                        <td><label for="remarks_{{ $criterion['id'] }}" class="sr-only">Evidence / remarks for {{ $criterion['area'] }}</label><textarea id="remarks_{{ $criterion['id'] }}" name="remarks[{{ $criterion['id'] }}]" rows="3" maxlength="5000" placeholder="Enter supporting evidence">{{ old('remarks.'.$criterion['id'], $criterion['remarks'] ?? '') }}</textarea></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot><tr><th colspan="4">Overall average of completed supervisors</th><td colspan="7" x-text="overallScore()"></td></tr></tfoot>
        </table>
    </div>
</section>
