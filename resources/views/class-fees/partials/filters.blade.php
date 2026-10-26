@if($studentList ?? false)
    @include('class-fees.partials.student-filters')
@else
        <form method="GET" action="{{ route('class-fees.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 rounded-lg bg-white p-5 shadow-sm"
              @if($studentList ?? false) data-register-filters @else x-data="{ selectedExamDates: @js($selectedExamDates), allExamDates: @js($examOptions->all()) }" @endif>
            @if($studentList ?? false)<input type="hidden" name="view" value="students">@endif
            <input type="hidden" name="exam_selection" value="1">
            <input type="hidden" name="selection_scope" value="{{ $filters['selection_scope'] }}">
            <input type="hidden" name="type" value="{{ $type }}">
            <div>
                <x-input-label for="class_room_id" value="Class" />
                <select name="class_room_id" id="class_room_id" class="mt-1 w-full rounded-md border-gray-300">
                    <option value="">{{ ($studentList ?? false) ? 'All classes' : 'All classes — overview' }}</option>
                    @foreach($classes as $option)<option value="{{ $option->id }}" @selected($class?->id === $option->id)>{{ $option->name }}{{ $option->active ? '' : ' (inactive)' }}</option>@endforeach
                </select>
            </div>
            <div>
                <x-input-label for="year" value="Installment year" />
                <x-text-input type="number" id="year" name="year" min="2000" max="2099" :value="$year" class="mt-1 w-full" />
                <label class="mt-2 text-xs text-gray-600 flex items-center gap-2"><input type="checkbox" name="all_years" value="1" @checked($filters['all_years'] ?? false)> All years (including earlier dues)</label>
            </div>
            <div>
                <x-input-label for="status" value="Payment status" />
                <select name="status" id="status" class="mt-1 w-full rounded-md border-gray-300">
                    @foreach(['all' => 'All students', 'unpaid' => 'Unpaid / balance due', 'paid' => 'All due installments settled', 'partial' => 'Some payments made', 'pending' => 'Pending cheque'] as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? 'all') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="q" value="Student / admission number" />
                <x-text-input id="q" name="q" type="search" maxlength="100" autocomplete="off" :value="$filters['q'] ?? ''" class="mt-1 w-full" />
            </div>
            <div class="flex flex-col justify-end gap-2">
                <label class="text-xs text-gray-600 flex items-center gap-2"><input type="checkbox" name="include_inactive" value="1" @checked($filters['include_inactive'] ?? false)> Include inactive / alumni</label>
                <label class="text-xs text-gray-600 flex items-center gap-2"><input type="checkbox" name="include_former" value="1" @checked($filters['include_former'] ?? false)> Include former class students</label>
                <x-primary-button>Apply filters</x-primary-button>
                @can('reports.download')
                    <button type="submit" name="pdf" value="1" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700">Download PDF</button>
                    @if($studentList ?? false)<button type="submit" name="download" value="csv" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700">Download CSV</button>@endif
                @endcan
            </div>
            <div data-register-terms class="sm:col-span-2 lg:col-span-5 border-t border-gray-100 pt-4 space-y-3">
                @include('class-fees.partials.exam-selection')
            </div>
        </form>

@endif
