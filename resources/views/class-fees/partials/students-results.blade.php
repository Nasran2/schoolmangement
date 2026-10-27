@include('class-fees.partials.student-pagination', ['position' => 'top'])
@forelse($displaySections as $section)
    @php
        $sectionName = $section['class']?->name ?? 'No class assigned';
        $columns = \App\Support\ClassFeeTableColumns::forCycles($section['cycles']);
    @endphp
    <section class="fee-register-section">
        <h3 class="fee-register-section-title">{{ $sectionName }}<span>{{ $columns->contains('kind', 'term') ? 'Fees grouped by term' : 'Annual facility fees' }}</span></h3>
        <div class="fee-register-table-scroll" tabindex="0" role="region" aria-label="{{ $sectionName }} student fees">
            <table class="fee-register-table">
                <thead><tr>
                    <th scope="col" class="fee-register-student-column">Student / admission</th>
                    @foreach($columns as $column)
                        <th scope="col" class="{{ $column['kind'] === 'facility' ? 'fee-register-facility-column' : 'fee-register-term-column' }}">
                            <div class="fee-register-column-title">{{ $column['label'] }}@if($column['kind'] === 'term') <span>{{ $column['due']->format('Y') }}</span>@endif</div>
                            @if($column['kind'] === 'term')
                                <div class="fee-register-sub">{{ $column['due']->format('d M') }}@if($column['end']) – {{ $column['end']->format('d M Y') }}@else {{ $column['due']->format('Y') }}@endif</div>
                            @else
                                <div class="fee-register-sub">Annual fee</div>
                            @endif
                            <div class="fee-register-rates">
                                @foreach($column['cycles'] as $cycle)
                                    <span>{{ $column['kind'] === 'facility' ? $cycle['due']->format('d M Y') : ($cycle['schedule']->fee_type === 'computer' ? 'Computer' : 'Exam') }} <strong>Rs {{ number_format($cycle['amount'], 2) }}</strong></span>
                                @endforeach
                            </div>
                        </th>
                    @endforeach
                    @if($columns->isEmpty())<th scope="col">Fees</th>@endif
                    <th scope="col" class="fee-register-due-column">Due now (Rs)<div class="fee-register-sub">All selected fees</div></th>
                </tr></thead>
                <tbody>
                    @foreach($section['rows'] as $row)
                        <tr>
                            <th scope="row" class="fee-register-student-column"><div class="fee-register-student-name">{{ $row['student']->name }}</div><div class="fee-register-sub">{{ $row['student']->admission_number }}@if($section['class'] && (int) $row['student']->class_room_id !== (int) $section['class']->id)<br>Former class @endif @if(!$row['student']->active || $row['student']->alumni)<br>Inactive / alumni @endif</div></th>
                            @foreach($columns as $column)
                                <td class="{{ $column['kind'] === 'facility' ? 'fee-register-facility-column' : 'fee-register-term-column' }}">
                                    @foreach($column['cycles'] as $cycle)
                                        @include('class-fees.partials.student-fee')
                                    @endforeach
                                    @can('revenue.add')
                                        @php
                                            $exam = $column['cycles']->first(fn ($cycle) => $cycle['schedule']->fee_type === 'exam');
                                            $computer = $column['cycles']->first(fn ($cycle) => $cycle['schedule']->fee_type === 'computer');
                                        @endphp
                                        @if($exam && $computer && $row['installments'][$exam['key']]['collectable'] > 0 && $row['installments'][$computer['key']]['collectable'] > 0)
                                            <a class="fee-register-combined-pay" href="{{ route('revenue.items.create', ['quick' => 'exam', 'student_id' => $row['student']->id, 'year' => $exam['due']->year, 'cycle' => $exam['key'], 'all_years' => 1, 'include_inactive' => 1]) }}">Pay both fees</a>
                                        @endif
                                    @endcan
                                </td>
                            @endforeach
                            @if($columns->isEmpty())<td class="text-gray-500">No selected fees in this period.</td>@endif
                            <td class="fee-register-due-column"><strong>{{ number_format($row['due'], 2) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@empty
    <div class="rounded-lg bg-white p-8 text-center text-sm text-gray-500">No students match these filters.</div>
@endforelse
@include('class-fees.partials.student-pagination', ['position' => 'bottom'])
