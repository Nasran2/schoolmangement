@php
    $pdfScoreSummary = \App\Services\TeacherEvaluations\EvaluationForm::scoreSummary($criteria);
    $pdfDisplayScore = $pdfScoreSummary['score'] ?? $pdfScoreSummary['entered_score'];
@endphp
<div class="pdf-score-summary">
    <div class="pdf-score-total">
        <strong>{{ $pdfScoreSummary['score'] !== null ? 'Overall score' : 'Score entered so far' }}:</strong>
        <span class="pdf-score-value">{{ $pdfDisplayScore !== null ? number_format($pdfDisplayScore, 2) : 'Not entered' }} / 100</span>
        <span class="pdf-score-state">{{ $pdfScoreSummary['score'] !== null ? \App\Services\TeacherEvaluations\EvaluationForm::band($pdfScoreSummary['score']) : 'Incomplete' }}</span>
        <p>{{ $pdfScoreSummary['score'] !== null ? 'Average of '.$pdfScoreSummary['completed'].' completed supervisor review(s). Partial reviews are excluded.' : ($pdfDisplayScore !== null ? 'Average points entered across started reviews. Missing ratings are pending; the final score has not been calculated.' : 'No ratings entered yet. The final score has not been calculated.') }}</p>
    </div>
    <table class="pdf-score-reviewers"><tr>
        @foreach($pdfScoreSummary['supervisors'] as $index => $supervisor)
            <td><strong>Supervisor {{ $index + 1 }}: {{ $supervisor['points'] !== null ? number_format($supervisor['points'], 2).' / 100' : 'Not started' }}</strong><br>{{ $supervisor['rated'] }} of {{ $supervisor['total'] }} ratings - {{ $supervisor['complete'] ? 'Complete' : ($supervisor['rated'] ? 'Incomplete' : 'Not started') }}</td>
        @endforeach
    </tr></table>
</div>
