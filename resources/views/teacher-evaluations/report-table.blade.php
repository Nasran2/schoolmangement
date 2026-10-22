@php
    $evidenceTable = $group === 'evidence';
    $widths = $evidenceTable ? [14, 21, 15, 15, 15, 9, 11] : [18, 21, 17, 18, 10, 16];
    $printRows = \App\Services\TeacherEvaluations\EvaluationForm::tableRows($entries, $fields);
@endphp
<table class="worksheet feedback-table {{ $blank ? 'writing-table' : '' }}">
    <thead><tr>
        @foreach($fields as $label)<th style="width:{{ $widths[$loop->index] }}%">{{ $label }}</th>@endforeach
    </tr></thead>
    <tbody>
        @forelse($printRows as $row)
            <tr class="{{ $row['entry'] % 2 === 0 ? 'stripe' : '' }}">
                @foreach($fields as $key => $label)
                    <td><div class="cell-text">@if($loop->first && $row['continued'])<span class="continued">Entry {{ $row['entry'] }} continued<br></span>@endif{{ str_ends_with($key, '_date') && $row['cells'][$key] ? $date($row['cells'][$key]) : $row['cells'][$key] }}</div></td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($fields) }}" class="empty">No entries recorded.</td></tr>
        @endforelse
    </tbody>
</table>
