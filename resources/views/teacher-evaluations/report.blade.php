@php
    $annual = $evaluation->type === 'annual';
    $criteria = $evaluation->criteria ?? \App\Services\TeacherEvaluations\EvaluationForm::criteria();
    $date = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d M Y') : '';
@endphp
<article class="evaluation-report {{ !$annual && !$blank ? 'compact-report' : '' }}">
    <div class="document-banner"><h1>{{ $schoolName ?? config('app.name') }}</h1><span>TEACHER EVALUATION</span></div>
    <h2>{{ $annual ? 'Annual Teacher Performance Appraisal' : 'Evidence, Feedback and Development Plan' }}{{ $blank ? ' - Blank Form' : '' }}</h2>
    <p class="muted">Confidential staff record. {{ $blank ? 'Complete by hand. Use additional pages if more space is needed.' : 'Record #'.$evaluation->id.' | '.ucfirst($evaluation->status).' | Exported '.now()->format('d M Y') }}</p>
    <table class="meta"><tr><td><strong>Teacher:</strong> {{ $evaluation->teacher_name ?: '________________________________________' }}</td><td><strong>Staff ID:</strong> {{ $evaluation->staff_id ?: '________________________' }}</td></tr><tr><td><strong>Section:</strong> {{ $evaluation->section ?: 'Preschool / Primary / Secondary / Other' }}</td><td><strong>Subject / class:</strong> {{ $evaluation->subject_class ?: '________________________________' }}</td></tr><tr><td><strong>Evaluator:</strong> {{ $evaluation->evaluator ?: '________________________________________' }}</td><td><strong>Review date:</strong> {{ $date($evaluation->review_date) ?: '________________________' }}</td></tr>
        @if($annual)<tr><td><strong>Appraisal year:</strong> {{ $blank ? '____________' : $evaluation->appraisal_year }} <strong>Years of service:</strong> {{ $evaluation->years_of_service ?: '____________' }}</td><td><strong>Period:</strong> {{ $date($evaluation->period_start) ?: '________________' }} to {{ $date($evaluation->period_end) ?: '________________' }}</td></tr>@else<tr><td><strong>Event / function:</strong> {{ $evaluation->event_name ?: '________________________________' }}</td><td><strong>Event date:</strong> {{ $date($evaluation->event_date) ?: '________________________' }}</td></tr>@endif
    </table>
    @if($annual)
        @if(!$blank && ($showScoreSummary ?? false))
            @include('teacher-evaluations.report-score-summary')
        @endif
        <p><strong>Rating scale:</strong> 5 Outstanding | 4 Exceeds expectations | 3 Meets expectations | 2 Needs improvement | 1 Unsatisfactory</p>
        @php($firstPageCriteriaCount = !$blank && ($showScoreSummary ?? false) ? 5 : 6)
        @foreach([array_slice($criteria, 0, $firstPageCriteriaCount), array_slice($criteria, $firstPageCriteriaCount)] as $chunk)
            @if(!$loop->first)<div class="new-page"><h2>{{ $evaluation->teacher_name ?: 'Teacher: ____________________' }} - Annual criteria continued</h2><p class="muted">Appraisal year: {{ $blank ? '____________' : $evaluation->appraisal_year }} | {{ $evaluation->status === 'draft' && !$blank ? 'Draft' : '' }}</p></div>@endif
            <table class="criteria"><thead><tr><th style="width:3%">No.</th><th style="width:18%">Performance area</th><th style="width:28%">Expected standard / evidence</th><th style="width:5%">Weight %</th>@foreach([1, 2, 3] as $supervisor)<th style="width:5%">S{{ $supervisor }}<br>Rating</th><th style="width:6%">S{{ $supervisor }}<br>Score</th>@endforeach<th style="width:13%">Remarks</th></tr></thead><tbody>
                @foreach($chunk as $criterion)
                    <tr class="{{ $blank ? 'blank' : '' }}"><td>{{ $criterion['id'] }}</td><td><strong>{{ $criterion['area'] }}</strong></td><td>{{ $criterion['standard'] }}</td><td class="number">{{ $criterion['weight'] }}</td>
                        @foreach(\App\Services\TeacherEvaluations\EvaluationForm::ratings($criterion) as $rating)
                            <td class="number">{{ $rating ?? '' }}</td><td class="number">{{ $rating !== null ? number_format($criterion['weight'] * $rating / 5, 1) : '' }}</td>
                        @endforeach
                        <td>{{ !$blank && !empty($criterion['remarks']) ? 'See criterion '.$criterion['id'].' remarks below.' : '' }}</td>
                    </tr>
                @endforeach
            </tbody></table>
        @endforeach
        <table class="supervisors"><tr>
            @foreach(\App\Services\TeacherEvaluations\EvaluationForm::supervisorScores($criteria) as $index => $supervisorScore)
                <td><strong>Supervisor {{ $index + 1 }}:</strong> {{ $evaluation->supervisors[$index] ?? ($index === 0 ? $evaluation->evaluator : null) ?: '____________________' }}<br>Score: {{ $blank ? '________ / 100' : ($supervisorScore !== null ? number_format($supervisorScore, 2).' / 100' : 'Not completed') }}</td>
            @endforeach
        </tr></table>
        <div class="score"><strong>Total weight: 100%. Overall performance score:</strong> {{ $blank ? '____________ / 100' : ($evaluation->score !== null ? number_format($evaluation->score, 2).' / 100 - '.$evaluation->band : 'Incomplete ratings - final score not calculated') }}</div>
        <p class="muted">S1 / S2 / S3 = Supervisor 1 / 2 / 3. Rating: 1-5. Score = weight x rating / 5. One supervisor is enough; the others are optional. The overall score averages completed supervisors only. Missing ratings may be left blank; only completed supervisor reviews contribute to the overall score.</p>
    @else
        <h3 class="section-bar">Assigned responsibility / duties</h3><div class="text response duties {{ $blank ? 'blank' : '' }}">{{ $evaluation->responsibility }}</div>
    @endif
    @foreach(['evidence' => ['title' => 'A. Evidence and examples', 'fields' => \App\Services\TeacherEvaluations\EvaluationForm::EVIDENCE_FIELDS], 'development' => ['title' => 'B. Professional development action plan', 'fields' => \App\Services\TeacherEvaluations\EvaluationForm::DEVELOPMENT_FIELDS]] as $group => $settings)
        @if(($evaluation->$group ?? []) || (!$annual && $blank))
            <h3 class="section-bar {{ $annual && $group === 'evidence' ? 'new-page' : '' }}">{{ $settings['title'] }}</h3>
            @include('teacher-evaluations.report-table', ['fields' => $settings['fields'], 'entries' => $blank ? array_fill(0, 4, []) : ($evaluation->$group ?? [])])
        @endif
    @endforeach
    <div @if($annual || $blank)class="new-page"@endif>
        @if($annual || $blank)<h2>{{ $evaluation->teacher_name ?: 'Teacher: ____________________' }} - Comments and acknowledgement</h2>@endif
        @if($annual)
        <h3 class="section-bar">Performance bands and suggested management action</h3><table class="meta band-table"><tr><td>90-100: Outstanding</td><td>Consider promotion or a higher increment, subject to approval and budget.</td></tr><tr><td>80 to below 90: Exceeds expectations</td><td>Consider an increment and expanded responsibilities.</td></tr><tr><td>70 to below 80: Meets expectations well</td><td>Consider an increment in line with policy and set growth goals.</td></tr><tr><td>60 to below 70: Needs improvement</td><td>Document an improvement plan and review date.</td></tr><tr><td>Below 60: Unsatisfactory</td><td>Set a formal improvement plan and follow-up review.</td></tr></table>
        <p class="muted">Ratings need evidence and consistent standards for each role and section. Scores do not guarantee salary changes or promotion. Final decisions require management review and school policy.</p>
        @endif
        <h3 class="section-bar">C. Teacher's comments</h3><div class="text response {{ $blank ? 'blank' : '' }}">{{ $evaluation->teacher_comments }}</div>
        <h3 class="section-bar">Management decision / comments</h3><div class="text response {{ $blank ? 'blank' : '' }}">{{ $evaluation->management_comments }}</div>
        <table class="meta signatures"><tr><td><strong>Teacher signature:</strong> ______________________________<br><strong>Recorded name:</strong> {{ $evaluation->teacher_signed_name ?: '______________________________' }}<br><strong>Date:</strong> {{ $date($evaluation->teacher_signed_date) ?: '________________________' }}</td><td><strong>Evaluator signature:</strong> ______________________________<br><strong>Recorded name:</strong> {{ $evaluation->evaluator_signed_name ?: '______________________________' }}<br><strong>Date:</strong> {{ $date($evaluation->evaluator_signed_date) ?: '________________________' }}</td></tr></table>
        <p class="muted">A signature confirms the discussion, not necessarily agreement. Recorded names and dates are transcribed from physical signatures, not digital signatures.</p>
        <h3 class="section-bar">Follow-up review</h3><p><strong>Completed date:</strong> {{ $date($evaluation->follow_up_completed_date) ?: '________________________' }}</p><div class="text response {{ $blank ? 'blank' : '' }}">{{ $evaluation->follow_up_notes }}</div>
        @if($annual && !$blank)
            @php($criterionRemarks = collect($criteria)->filter(fn($criterion) => !empty($criterion['remarks']))->map(fn($criterion) => ['area' => 'Criterion '.$criterion['id'].' - '.$criterion['area'], 'remarks' => $criterion['remarks']])->values()->all())
            @if(count($criterionRemarks))
                <h3 class="section-bar">Criterion evidence and remarks</h3>
                <table class="worksheet remarks-table"><thead><tr><th style="width:25%">Performance area</th><th style="width:75%">Evidence / remarks</th></tr></thead><tbody>
                    @foreach(\App\Services\TeacherEvaluations\EvaluationForm::tableRows($criterionRemarks, ['area' => '', 'remarks' => ''], 1000) as $remarkRow)
                        <tr class="{{ $remarkRow['entry'] % 2 === 0 ? 'stripe' : '' }}"><td><strong>{{ $remarkRow['cells']['area'] ?: 'Continued' }}</strong></td><td class="text">{{ $remarkRow['cells']['remarks'] }}</td></tr>
                    @endforeach
                </tbody></table>
            @endif
        @endif
    </div>
</article>
