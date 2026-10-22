@php
    $scoreSummary = \App\Services\TeacherEvaluations\EvaluationForm::scoreSummary($evaluation->criteria ?? []);
    $displayScore = $scoreSummary['score'] ?? $scoreSummary['entered_score'];
@endphp
<section class="evaluation-score-summary" aria-labelledby="evaluation-score-title">
    <div class="evaluation-score-overall">
        <div class="evaluation-score-heading">
            <h3 id="evaluation-score-title">{{ $scoreSummary['score'] !== null ? 'Overall score' : 'Score entered so far' }}</h3>
            <span class="evaluation-score-badge">{{ $scoreSummary['score'] !== null ? 'Calculated' : 'Incomplete' }}</span>
        </div>
        <p class="evaluation-score-value">{{ $displayScore !== null ? number_format($displayScore, 2) : '—' }} <span>/ 100</span></p>
        @if($scoreSummary['score'] !== null)
            <p class="evaluation-score-band">{{ \App\Services\TeacherEvaluations\EvaluationForm::band($scoreSummary['score']) }}</p>
            <p class="evaluation-score-note">Average of {{ $scoreSummary['completed'] }} completed supervisor {{ \Illuminate\Support\Str::plural('review', $scoreSummary['completed']) }}. Partial reviews are excluded.</p>
        @elseif($displayScore !== null)
            <p class="evaluation-score-note">Average points entered across started reviews. Missing ratings are still pending; the final score has not been calculated.</p>
        @else
            <p class="evaluation-score-note">No ratings entered yet. Scores will appear here as ratings are saved.</p>
        @endif
    </div>
    <div class="evaluation-score-supervisors">
        @foreach($scoreSummary['supervisors'] as $index => $supervisor)
            <div class="evaluation-score-reviewer {{ $supervisor['complete'] ? 'is-complete' : '' }}">
                <h4>Supervisor {{ $index + 1 }}</h4>
                <p class="evaluation-score-reviewer-name">{{ $evaluation->supervisors[$index] ?? ($index === 0 ? $evaluation->evaluator : null) ?: 'Name not provided' }}</p>
                <p class="evaluation-score-points">{{ $supervisor['points'] !== null ? number_format($supervisor['points'], 2) : '—' }} <span>/ 100</span></p>
                <p class="evaluation-score-progress">{{ $supervisor['rated'] }} of {{ $supervisor['total'] }} ratings · {{ $supervisor['complete'] ? 'Complete' : ($supervisor['rated'] ? 'Incomplete' : 'Not started') }}</p>
            </div>
        @endforeach
    </div>
</section>
