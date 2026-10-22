<div class="teacher-profile">
    <section class="teacher-profile-hero" aria-label="Teacher overview">
        <div class="teacher-profile-identity">
            <div class="teacher-profile-avatar">{{ $initials }}</div>
            <div class="teacher-profile-name">
                <p class="teacher-profile-eyebrow">Teaching staff</p>
                <h1>{{ $person->name }}</h1>
                <div class="teacher-profile-meta"><span><x-profile-icon name="book"/>{{ $person->assigned_classes ?: 'No class assigned' }}</span>@if($person->joining_date)<span><x-profile-icon name="calendar"/>Joined {{ $person->joining_date->format('d M Y') }}</span>@endif</div>
            </div>
            <div class="teacher-profile-state"><span class="profile-status {{ $person->active ? 'is-active' : 'is-inactive' }}"><span></span>{{ $person->active ? 'Active' : 'Inactive' }}</span><p><x-profile-icon name="lock"/>Details are read-only</p></div>
        </div>
        <div class="teacher-profile-actions">
            <div class="teacher-profile-primary-actions">
                @can('teacher_evaluations.manage')
                    <a class="profile-button is-primary" href="{{ route('teacher-evaluations.create', ['teacher_id' => $person->id, 'type' => 'annual']) }}"><x-profile-icon name="plus"/>New annual appraisal</a>
                    <a class="profile-button is-teal" href="{{ route('teacher-evaluations.create', ['teacher_id' => $person->id, 'type' => 'event']) }}"><x-profile-icon name="plus"/>New event review</a>
                @endcan
                @can('teacher_evaluations.view')<a class="profile-button is-plain" href="#evaluations">View evaluations<x-profile-icon name="arrow"/></a>@endcan
            </div>
            @can('teacher_evaluations.export')<a class="profile-button is-outline" href="#templates"><x-profile-icon name="download"/>Blank forms</a>@endcan
        </div>
    </section>

    @can('teacher_evaluations.view')
        <div class="teacher-profile-stats" aria-label="Evaluation summary">
            @foreach([
                ['key' => 'annual', 'label' => 'Annual appraisals', 'note' => 'Yearly performance reviews', 'icon' => 'document', 'tone' => 'purple'],
                ['key' => 'event', 'label' => 'Event reviews', 'note' => 'Feedback for school functions', 'icon' => 'calendar', 'tone' => 'blue'],
                ['key' => 'completed', 'label' => 'Completed', 'note' => 'Reports marked complete', 'icon' => 'check', 'tone' => 'teal'],
                ['key' => 'draft', 'label' => 'Drafts', 'note' => 'Reports still being prepared', 'icon' => 'clock', 'tone' => 'amber'],
            ] as $stat)
                <div class="profile-stat tone-{{ $stat['tone'] }}"><div class="profile-stat-top"><span>{{ $stat['label'] }}</span><span class="profile-stat-icon"><x-profile-icon :name="$stat['icon']"/></span></div><p class="profile-stat-number">{{ $stat['key'] === 'draft' ? max(0, $evaluationStats['annual'] + $evaluationStats['event'] - $evaluationStats['completed']) : $evaluationStats[$stat['key']] }}</p><p class="profile-stat-note">{{ $stat['note'] }}</p></div>
            @endforeach
        </div>
    @endcan

    <div class="teacher-profile-grid {{ auth()->user()->canAny(['teacher_evaluations.view', 'teacher_evaluations.export']) ? '' : 'is-single' }}">
        <div class="teacher-profile-main">
            <section class="profile-panel">
                <div class="profile-panel-heading"><span class="profile-panel-icon tone-purple"><x-profile-icon name="person"/></span><div><h2>Teacher details</h2><p>Teaching assignment and contact information.</p></div></div>
                <dl class="teacher-profile-fields">
                    <div class="profile-info"><span class="profile-info-icon"><x-profile-icon name="book"/></span><x-profile-value label="Assigned classes" :value="$person->assigned_classes"/></div>
                    <div class="profile-info"><span class="profile-info-icon"><x-profile-icon name="calendar"/></span><x-profile-value label="Joining date" :value="$person->joining_date"/></div>
                    <div class="profile-info"><span class="profile-info-icon"><x-profile-icon name="mail"/></span><x-profile-value label="Email" :value="$person->email"/></div>
                    <div class="profile-info"><span class="profile-info-icon"><x-profile-icon name="phone"/></span><x-profile-value label="Phone" :value="$person->phone"/></div>
                    <div class="profile-info is-wide"><span class="profile-info-icon"><x-profile-icon name="location"/></span><x-profile-value label="Address" :value="$person->address"/></div>
                </dl>
            </section>

            @can('teacher_evaluations.view')
                <section id="evaluations" class="profile-panel profile-history">
                    <div class="profile-panel-heading"><span class="profile-panel-icon tone-teal"><x-profile-icon name="document"/></span><div><h2>Evaluation history</h2><p>Recent annual appraisals and event reviews.</p></div><a class="profile-history-all" href="{{ route('teacher-evaluations.index', ['teacher_id' => $person->id]) }}">View all reports<x-profile-icon name="arrow"/></a></div>
                    <div class="profile-history-list">
                        @forelse($history as $record)
                            <a href="{{ route('teacher-evaluations.show', $record) }}" class="profile-history-item">
                                <span class="profile-history-icon {{ $record->type === 'annual' ? 'tone-purple' : 'tone-blue' }}"><x-profile-icon :name="$record->type === 'annual' ? 'document' : 'calendar'"/></span>
                                <div class="profile-history-description"><h3>{{ $record->type === 'annual' ? $record->appraisal_year.' Annual appraisal' : $record->event_name }}</h3><p><span>{{ $record->type === 'annual' ? 'Annual review' : 'Event review' }}</span><span class="profile-history-separator">&middot;</span>{{ $record->review_date?->format('d M Y') ?: 'Review date not entered' }}</p></div>
                                <div class="profile-history-result">@if($record->score !== null)<span class="profile-history-score">{{ number_format($record->score, 1) }} <small>/ 100</small></span>@endif<span class="profile-status {{ $record->status === 'completed' ? 'is-active' : 'is-draft' }}"><span></span>{{ ucfirst($record->status) }}</span></div>
                                <x-profile-icon name="chevron" class="profile-history-chevron"/>
                            </a>
                        @empty
                            <div class="profile-history-empty"><span class="profile-panel-icon tone-purple"><x-profile-icon name="document"/></span><h3>No evaluations yet</h3><p>Add an annual appraisal or record feedback from a school event.</p></div>
                        @endforelse
                    </div>
                    @if($evaluationStats['annual'] + $evaluationStats['event'] > $history->count())<p class="profile-history-footnote">Showing the {{ $history->count() }} most recent reports. View all reports for the full history and filters.</p>@endif
                </section>
            @endcan
        </div>

        @canany(['teacher_evaluations.view', 'teacher_evaluations.export'])
            <aside class="teacher-profile-aside">
                @include('teacher-evaluations.template-links', ['teacher' => $person])
                @can('teacher_evaluations.view')
                <section class="profile-panel profile-review-guide">
                    <div class="profile-panel-heading"><span class="profile-panel-icon tone-blue"><x-profile-icon name="book"/></span><div><h2>Which review should I use?</h2><p>Choose the right form for the review.</p></div></div>
                    <div class="profile-guide-content"><div><span class="profile-guide-label tone-purple">Annual appraisal</span><p>Review the teacher’s performance across an appraisal year using the 13 weighted criteria.</p></div><div><span class="profile-guide-label tone-teal">Event review</span><p>Record evidence, feedback and development actions for a school function or assigned responsibility.</p></div></div>
                    <a href="{{ route('teacher-evaluations.instructions') }}" class="profile-guide-link">Read the full instructions<x-profile-icon name="arrow"/></a>
                </section>
                @endcan
            </aside>
        @endcanany
    </div>
</div>
