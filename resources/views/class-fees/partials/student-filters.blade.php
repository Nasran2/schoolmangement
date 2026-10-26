<form method="GET" action="{{ route('class-fees.index') }}" class="fee-register-filters" data-register-filters>
    <input type="hidden" name="view" value="students">
    <input type="hidden" name="exam_selection" value="1">
    <input type="hidden" name="selection_scope" value="{{ $filters['selection_scope'] }}">
    <input type="hidden" name="type" value="{{ $type }}">
    <div class="fee-register-filter-grid">
        <div><label for="class_room_id">Class</label><select name="class_room_id" id="class_room_id"><option value="">All classes</option>@foreach($classes as $option)<option value="{{ $option->id }}" @selected($class?->id === $option->id)>{{ $option->name }}{{ $option->active ? '' : ' (inactive)' }}</option>@endforeach</select></div>
        <div><label for="year">Year</label><input type="number" id="year" name="year" min="2000" max="2099" value="{{ $year }}"></div>
        <div><label for="status">Payment status</label><select name="status" id="status">@foreach(['all' => 'All students', 'unpaid' => 'Unpaid / balance due', 'paid' => 'All due installments settled', 'partial' => 'Some payments made', 'pending' => 'Pending cheque'] as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? 'all') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div><label for="q">Student / admission number</label><input id="q" name="q" type="search" maxlength="100" autocomplete="off" value="{{ $filters['q'] ?? '' }}" placeholder="Search instantly…"></div>
        <div><label for="per_page">Rows per page</label><select name="per_page" id="per_page">@foreach([10, 25, 50, 100] as $size)<option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 25) === $size)>{{ $size }}</option>@endforeach</select></div>
    </div>
    <div class="fee-register-filter-toolbar">
        <div class="fee-register-filter-options">
            <label><input type="checkbox" name="all_years" value="1" @checked($filters['all_years'] ?? false)> All years <span class="fee-register-option-detail">(including earlier dues)</span></label>
            <label><input type="checkbox" name="include_inactive" value="1" @checked($filters['include_inactive'] ?? false)> Include inactive / alumni</label>
            <label><input type="checkbox" name="include_former" value="1" @checked($filters['include_former'] ?? false)> Include former class students</label>
        </div>
        <div class="fee-register-filter-downloads">
            <button type="submit" class="fee-register-button">Apply filters</button>
            @can('reports.download')
                <button type="submit" name="pdf" value="1" class="fee-register-button">Download PDF</button>
                <button type="submit" name="download" value="csv" class="fee-register-button">Download CSV</button>
            @endcan
        </div>
    </div>
    <div data-register-terms>@include('class-fees.partials.exam-selection')</div>
</form>
