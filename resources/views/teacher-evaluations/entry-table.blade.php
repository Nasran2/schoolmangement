<section class="evaluation-sheet overflow-hidden rounded-xl border bg-white shadow-sm">
    <div class="evaluation-sheet-heading"><div><h3>{{ $group === 'evidence' ? 'A.' : 'B.' }} {{ $settings['title'] }}</h3><p>{{ $group === 'evidence' ? 'Record specific examples, strengths, improvement areas and agreed actions.' : 'Record development goals, support, success measures and target dates.' }}</p></div><button type="button" @click="{{ $group }}.length < 50 && {{ $group }}.push({{ \Illuminate\Support\Js::from(array_fill_keys(array_keys($settings['fields']), '')) }})" :disabled="{{ $group }}.length >= 50" class="evaluation-add-row">+ Add entry</button></div>
    <p class="evaluation-scroll-hint">Each row is one entry. Scroll sideways for all columns. Leave unused rows blank.</p>
    <div class="evaluation-grid-scroll" role="region" aria-label="{{ $settings['title'] }} worksheet" tabindex="0">
        <table class="evaluation-entry-table {{ $group === 'evidence' ? 'evidence-entry-table' : 'development-entry-table' }}">
            <thead><tr><th style="width:45px">No.</th>@foreach($settings['fields'] as $key => $label)<th style="width:{{ str_ends_with($key, '_date') ? '155' : (in_array($key, ['examples', 'notes', 'training']) ? '240' : '185') }}px">{{ $label }}</th>@endforeach<th style="width:85px">Action</th></tr></thead>
            <tbody>
                <template x-for="(entry, index) in {{ $group }}" :key="index">
                    <tr><td class="evaluation-row-number" x-text="index + 1"></td>
                        @foreach($settings['fields'] as $key => $label)
                            <td><label class="sr-only" :for="'{{ $group }}_' + index + '_{{ $key }}'" x-text="'{{ $label }} — entry ' + (index + 1)"></label>
                                @if(str_ends_with($key, '_date'))
                                    <input type="date" :id="'{{ $group }}_' + index + '_{{ $key }}'" :name="'{{ $group }}[' + index + '][{{ $key }}]'" x-model="entry.{{ $key }}">
                                @else
                                    <textarea :id="'{{ $group }}_' + index + '_{{ $key }}'" :name="'{{ $group }}[' + index + '][{{ $key }}]'" x-model="entry.{{ $key }}" rows="4" maxlength="5000" placeholder="{{ $label }}"></textarea>
                                @endif
                            </td>
                        @endforeach
                        <td><button type="button" class="evaluation-remove-row" :aria-label="'Remove {{ $group }} entry ' + (index + 1)" @click="{{ $group }}.splice(index, 1)">Remove entry</button></td>
                    </tr>
                </template>
                <tr x-show="{{ $group }}.length === 0"><td colspan="{{ count($settings['fields']) + 2 }}" class="evaluation-empty">No entries. Use Add entry to create one.</td></tr>
            </tbody>
        </table>
    </div>
    <p class="evaluation-sheet-note">{{ $group === 'evidence' ? 'Missing performance areas, evidence or reviewer names are listed in the save warning. You can still save the report.' : 'Missing goals, actions, success measures or target dates are listed in the save warning. You can still save the report.' }}</p>
</section>
