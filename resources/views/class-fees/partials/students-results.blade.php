@include('class-fees.partials.student-pagination', ['position' => 'top'])
@forelse($displaySections as $section)
    @php($sectionName = $section['class']?->name ?? 'No class assigned')
    <section class="fee-register-section">
        <h3 class="fee-register-section-title">{{ $sectionName }}</h3>
        @foreach($section['cycles']->isEmpty() ? collect([collect()]) : $section['cycles']->chunk(4) as $chunk)
            <div class="overflow-x-auto">
                <table class="fee-register-table"><thead><tr><th>Student / admission</th>
                    @foreach($chunk as $cycle)<th><div>{{ \App\Models\ClassFeeSchedule::TYPES[$cycle['schedule']->fee_type] }} @if(isset($cycle['term']))· Term {{ $cycle['term'] }}@endif</div><div class="fee-register-sub">{{ $cycle['due']->format('d M Y') }}@if(isset($cycle['end'])) – {{ $cycle['end']->format('d M Y') }}@endif<br>Rs {{ number_format($cycle['amount'], 2) }}</div></th>@endforeach
                    @if($chunk->isEmpty())<th>Fees</th>@endif<th style="min-width:110px;text-align:right;">Due now (Rs)<div class="fee-register-sub">All selected fees</div></th>
                </tr></thead><tbody>
                    @foreach($section['rows'] as $row)
                        <tr><td><div class="font-semibold text-gray-900">{{ $row['student']->name }}</div><div class="fee-register-sub">{{ $row['student']->admission_number }}@if($section['class'] && (int) $row['student']->class_room_id !== (int) $section['class']->id)<br>Former class @endif @if(!$row['student']->active || $row['student']->alumni)<br>Inactive / alumni @endif</div></td>
                            @foreach($chunk as $key => $cycle)
                                @php($installment = $row['installments'][$key])
                                <td><div class="fee-register-status-line"><div class="font-semibold {{ $installment['status'] === 'Paid' ? 'text-green-700' : ($installment['due'] > 0 ? 'text-red-700' : 'text-gray-600') }}">{{ $installment['status'] }}</div>
                                    @if($installment['eligible'] || $installment['paid'] > 0 || $installment['pending'] > 0)
                                        <div class="fee-register-sub">Paid Rs {{ number_format($installment['paid'], 2) }}@if($installment['pending'] > 0)<br>Pending Rs {{ number_format($installment['pending'], 2) }}@endif @if($installment['waived'] > 0)<br>Waived Rs {{ number_format($installment['waived'], 2) }}@endif</div>
                                    @endif
                                    </div>
                                    @if($installment['eligible'] || $installment['paid'] > 0 || $installment['pending'] > 0)
                                        <div class="fee-register-actions">
                                            @can('revenue.add')
                                                @if($installment['eligible'] && $installment['collectable'] > 0)
                                                    <a class="fee-register-button" data-register-action="pay" href="{{ route('class-fees.index', ['class_room_id' => $section['class']->id, 'type' => $type, 'year' => $cycle['due']->year, 'student_id' => $row['student']->id, 'cycle' => $key, 'include_inactive' => 1, 'include_former' => 1, 'include_facility' => 1]) }}#collect-fee">{{ $cycle['due']->gt(today()) ? 'Pay in advance' : 'Pay' }}</a>
                                                @endif
                                            @endcan
                                            @can('revenue.delete')
                                                @if($installment['paid'] > 0)<a class="fee-register-button fee-register-unpaid" data-register-action="unpaid" href="{{ route('class-fees.index', ['class_room_id' => $section['class']->id, 'type' => $type, 'year' => $cycle['due']->year, 'student_id' => $row['student']->id, 'cycle' => $key, 'include_inactive' => 1, 'include_former' => 1, 'include_facility' => 1]) }}">Make unpaid</a>@endif
                                            @endcan
                                        </div>
                                        @can('revenue.manage')
                                            @foreach($installment['receipts']->reject(fn ($receipt) => $receipt->isCancelled()) as $receipt)
                                                @php($source = $receipt instanceof \App\Models\Revenue ? 'revenue' : 'history')
                                                <div class="fee-register-sub">{{ $source === 'revenue' ? ($receipt->bill_no ?: 'Receipt #'.$receipt->id) : 'History #'.$receipt->id }} <a class="text-indigo-700 underline" href="{{ route('class-fees.payments.edit', ['source' => $source, 'payment' => $receipt->id]) }}">Edit payment</a></div>
                                            @endforeach
                                        @endcan
                                    @endif
                                </td>
                            @endforeach
                            @if($chunk->isEmpty())<td class="text-gray-500">No selected fees in this period.</td>@endif
                            <td style="text-align:right;font-weight:600;">{{ number_format($row['due'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody></table>
            </div>
        @endforeach
    </section>
@empty
    <div class="rounded-lg bg-white p-8 text-center text-sm text-gray-500">No students match these filters.</div>
@endforelse
@include('class-fees.partials.student-pagination', ['position' => 'bottom'])
