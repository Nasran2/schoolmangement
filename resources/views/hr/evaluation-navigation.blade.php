@can('teacher_evaluations.view')
    <div class="mt-2">
        <x-sidebar-section-header name="evaluations" label="Evaluations" :active="$isEvaluations" :href="route('teacher-evaluations.index')">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 4H5v17h14V4h-4M9 3h6v4H9zM8 12h8M8 16h8"/></svg>
        </x-sidebar-section-header>
        <div x-show="menus.evaluations" class="mt-1 ml-5 space-y-1 border-l border-gray-200 pl-3" x-cloak>
            <a class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('teacher-evaluations.index', 'teacher-evaluations.show', 'teacher-evaluations.edit') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}" href="{{ route('teacher-evaluations.index') }}">All evaluations</a>
            @can('teacher_evaluations.manage')
                <a class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-50" href="{{ route('teacher-evaluations.create', ['type' => 'annual']) }}">Add annual appraisal</a>
                <a class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-50" href="{{ route('teacher-evaluations.create', ['type' => 'event']) }}">Add event review</a>
            @endcan
            @can('hr.directory.view')<a class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-50" href="{{ route('hr.teachers.index') }}">Browse teachers</a>@endcan
            @can('teacher_evaluations.export')<a class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-50" href="{{ route('teacher-evaluations.index') }}#templates">Download templates</a>@endcan
            <a class="block rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-50" href="{{ route('teacher-evaluations.instructions') }}">How to use evaluations</a>
        </div>
    </div>
@endcan
