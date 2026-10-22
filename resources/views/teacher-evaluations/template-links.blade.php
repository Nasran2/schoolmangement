@php($teacher = $teacher ?? null)
@can('teacher_evaluations.export')
    <section id="templates" class="profile-panel profile-templates">
        <div class="profile-panel-heading"><span class="profile-panel-icon tone-purple"><x-profile-icon name="download"/></span><div><h3>Download a blank form</h3><p>{{ $teacher ? 'Teacher name and assigned class are prefilled.' : 'Print a form, complete it by hand, then enter the details.' }}</p></div></div>
        <div class="profile-template-list {{ $teacher ? '' : 'is-general' }}">
            @foreach(['annual' => ['label' => 'Annual appraisal', 'description' => '13 performance areas with up to 3 supervisors.', 'tone' => 'purple', 'icon' => 'document'], 'event' => ['label' => 'Event review', 'description' => 'Evidence, feedback and development actions.', 'tone' => 'teal', 'icon' => 'calendar']] as $type => $template)
                <div class="profile-template-card tone-{{ $template['tone'] }}">
                    <div class="profile-template-title"><span class="profile-template-icon"><x-profile-icon :name="$template['icon']"/></span><div><h4>{{ $template['label'] }}</h4><p>{{ $template['description'] }}</p></div></div>
                    <div class="profile-template-formats">
                        @foreach(['pdf' => 'PDF', 'xlsx' => 'Excel'] as $format => $formatLabel)
                            <a href="{{ route('teacher-evaluations.template', array_filter(['type' => $type, 'format' => $format, 'teacher_id' => $teacher?->id])) }}" class="profile-format-button is-{{ $format }}" aria-label="Download {{ $template['label'] }} template as {{ $formatLabel }}"><x-profile-icon name="download"/><span>{{ $formatLabel }}</span></a>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <p class="profile-template-note"><x-profile-icon name="document"/>Includes writing spaces and English instructions.</p>
        </div>
    </section>
@endcan
