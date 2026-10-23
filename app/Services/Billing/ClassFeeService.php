<?php

namespace App\Services\Billing;

use App\Models\ClassFeePaymentHistory;
use App\Models\ClassFeeSchedule;
use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassFeeService
{
    public function configure(ClassRoom $class, array $data): void
    {
        DB::transaction(function () use ($class, $data) {
            ClassRoom::whereKey($class->id)->lockForUpdate()->firstOrFail();
            $this->configureSchedules($class, $data);
        });
    }

    private function configureSchedules(ClassRoom $class, array $data): void
    {
        if (! empty($data['exam_terms']) && (float) ($data['exam_fee'] ?? 0) > 0) {
            $this->configureTerms($class, $data);
            unset($data['exam_fee']);
        }
        foreach (ClassFeeSchedule::TYPES as $type => $label) {
            if (! array_key_exists($type.'_fee', $data)) {
                continue;
            }
            $amount = round((float) ($data[$type.'_fee'] ?? 0), 2);
            $date = $data[$type.'_fee_start_date'] ?? null;
            $current = $class->feeSchedules()->where('fee_type', $type)->whereNull('ends_on')->lockForUpdate()->first();
            if ($current?->terms && $amount > 0 && empty($data['exam_terms']) && $type === 'exam'
                && ($amount !== (float) $current->amount || $date !== $current->first_due_date->toDateString())) {
                throw ValidationException::withMessages(['exam_terms' => 'Use the three term calendar to change this fee.']);
            }
            if ($current && $amount === (float) $current->amount && $date === $current->first_due_date->toDateString()) {
                continue;
            }
            if ($this->reviseUnpaidSchedule($class, $type, [
                'amount' => $amount, 'first_due_date' => $date, 'terms' => $current?->terms,
            ])) {
                continue;
            }
            $closedEnd = $class->feeSchedules()->where('fee_type', $type)->whereNotNull('ends_on')->max('ends_on');
            if ($amount > 0 && $closedEnd && $date <= Carbon::parse($closedEnd)->toDateString()) {
                throw ValidationException::withMessages([$type.'_fee_start_date' => 'Choose a starting date after the earlier fee schedule ends ('.Carbon::parse($closedEnd)->toDateString().').']);
            }
            $original = $class->feeSchedules()->where('fee_type', $type)->orderBy('first_due_date')->first();
            if ($amount > 0 && $original && $original->first_due_date->lte(today())) {
                $newDate = Carbon::parse($date);
                $months = ($newDate->year - $original->first_due_date->year) * 12 + $newDate->month - $original->first_due_date->month;
                if ($months < 0 || $months % $original->intervalMonths() !== 0 || ! $original->first_due_date->copy()->addMonthsNoOverflow($months)->isSameDay($newDate)) {
                    throw ValidationException::withMessages([
                        $type.'_fee_start_date' => 'Choose an upcoming due date on the existing '.($type === 'exam' ? '3-month' : 'annual').' schedule to avoid charging an extra installment.',
                    ]);
                }
            }
            if ($current) {
                $hasHistory = $current->first_due_date->lte(today()) || $current->revenues()->exists() || ClassFeePaymentHistory::where('class_fee_schedule_id', $current->id)->exists();
                if (! $hasHistory) {
                    if ($amount <= 0) {
                        $current->delete();
                    } else {
                        $current->update(['amount' => $amount, 'first_due_date' => $date]);
                    }

                    continue;
                }
                if ($amount > 0) {
                    $lastPaidDue = max($current->revenues()->max('class_fee_due_date') ?? '', ClassFeePaymentHistory::where('class_fee_schedule_id', $current->id)->whereNull('cancelled_at')->max('class_fee_due_date') ?? '');
                    if ($date <= today()->toDateString() || ($lastPaidDue && $date <= Carbon::parse($lastPaidDue)->toDateString())) {
                        throw ValidationException::withMessages([
                            $type.'_fee_start_date' => 'To change '.$label.', choose a future starting date after any prepaid installment. Earlier installments are kept.',
                        ]);
                    }
                }
                $end = $amount > 0 ? Carbon::parse($date)->subDay() : today();
                $lastPaidDue = max($current->revenues()->max('class_fee_due_date') ?? '', ClassFeePaymentHistory::where('class_fee_schedule_id', $current->id)->whereNull('cancelled_at')->max('class_fee_due_date') ?? '');
                if ($lastPaidDue && Carbon::parse($lastPaidDue)->gt($end)) {
                    $end = Carbon::parse($lastPaidDue);
                }
                $current->update(['ends_on' => $end->toDateString()]);
            }
            if ($amount <= 0) {
                continue;
            }
            $category = RevenueCategory::where('class_fee_type', $type)->first() ?? RevenueCategory::firstOrCreate(['name' => $label], [
                'payment_type' => $type === 'exam' ? '3_months' : 'yearly',
                'interval_months' => $type === 'exam' ? 3 : 12, 'active' => true, 'applies_to_all' => true,
            ]);
            $category->update(['class_fee_type' => $type]);
            $class->feeSchedules()->create([
                'fee_type' => $type, 'revenue_category_id' => $category->id,
                'amount' => $amount, 'first_due_date' => $date,
            ]);
        }
        $class->unsetRelation('feeSchedules');
    }

    /** Until a fee has payment records, its schedule is editable setup data. */
    private function reviseUnpaidSchedule(ClassRoom $class, string $type, array $attributes): bool
    {
        $schedules = $class->feeSchedules()->where('fee_type', $type)
            ->orderByDesc('first_due_date')->lockForUpdate()->get();
        if ($schedules->isEmpty()) {
            return false;
        }
        $ids = $schedules->pluck('id');
        // Include pending, historical and cancelled entries: all are accounting history.
        if (Revenue::whereIn('class_fee_schedule_id', $ids)->exists()
            || ClassFeePaymentHistory::whereIn('class_fee_schedule_id', $ids)->exists()) {
            return false;
        }
        if ((float) $attributes['amount'] <= 0) {
            $schedules->each(fn ($schedule) => $schedule->delete());
        } else {
            $current = $schedules->firstWhere('ends_on', null) ?? $schedules->first();
            $current->update($attributes + ['ends_on' => null]);
            // Consolidate setup-only versions, so outdated unpaid charges do not survive a correction.
            $schedules->reject(fn ($schedule) => $schedule->id === $current->id)
                ->each(fn ($schedule) => $schedule->delete());
        }

        return true;
    }

    /** Three editable terms repeat annually; old receipt identities move with their term. */
    private function configureTerms(ClassRoom $class, array $data): void
    {
        $terms = array_map(fn ($term) => ['start' => $term['start'], 'end' => $term['end']], array_values($data['exam_terms']));
        if (count($terms) !== 3) {
            throw ValidationException::withMessages(['exam_terms' => 'Enter exactly three terms.']);
        }
        foreach ($terms as $i => $term) {
            $start = Carbon::parse($term['start']);
            $end = Carbon::parse($term['end']);
            if ($end->lt($start) || ($i > 0 && $start->lte(Carbon::parse($terms[$i - 1]['end'])))
                || $end->gte(Carbon::parse($terms[0]['start'])->addYearNoOverflow())) {
                throw ValidationException::withMessages(['exam_terms' => 'Term dates must be in order, without overlap, within one academic year.']);
            }
        }
        $amount = round((float) $data['exam_fee'], 2);
        if ($this->reviseUnpaidSchedule($class, 'exam', [
            'amount' => $amount, 'first_due_date' => $terms[0]['start'], 'terms' => $terms,
        ])) {
            return;
        }
        $current = $class->feeSchedules()->where('fee_type', 'exam')->whereNull('ends_on')->lockForUpdate()->first();
        $closedEnd = $class->feeSchedules()->where('fee_type', 'exam')->whereNotNull('ends_on')->max('ends_on');
        if ($closedEnd && $terms[0]['start'] <= Carbon::parse($closedEnd)->toDateString()) {
            throw ValidationException::withMessages(['exam_terms' => 'The new calendar must start after the earlier schedule ends.']);
        }
        if (! $current) {
            $category = RevenueCategory::where('class_fee_type', 'exam')->first()
                ?? RevenueCategory::firstOrCreate(['name' => 'Exam Fee'], ['payment_type' => 'custom', 'interval_months' => 4, 'active' => true, 'applies_to_all' => true]);
            $category->update(['class_fee_type' => 'exam']);
            $closedEnd = $class->feeSchedules()->where('fee_type', 'exam')->max('ends_on');
            if ($closedEnd && $terms[0]['start'] <= $closedEnd) {
                throw ValidationException::withMessages(['exam_terms' => 'Start after the earlier fee schedule ends.']);
            }
            $class->feeSchedules()->create(['fee_type' => 'exam', 'revenue_category_id' => $category->id,
                'amount' => $amount, 'first_due_date' => $terms[0]['start'], 'terms' => $terms]);

            return;
        }
        if ($amount !== (float) $current->amount) {
            // A new calendar/rate can take effect in a future academic year.
            if ($terms[0]['start'] <= today()->toDateString() || ($terms[0]['start'] === $current->first_due_date->toDateString()
                && ($current->revenues()->whereNull('cancelled_at')->exists() || ClassFeePaymentHistory::where('class_fee_schedule_id', $current->id)->whereNull('cancelled_at')->exists()))) {
                throw ValidationException::withMessages(['exam_fee' => 'Keep the existing rate when correcting term dates. Start a new rate in a future academic year.']);
            }
        }
        if ($terms[0]['start'] > $current->first_due_date->toDateString() && $terms[0]['start'] > today()->toDateString()
            && ($current->first_due_date->lte(today()) || $current->revenues()->exists() || ClassFeePaymentHistory::where('class_fee_schedule_id', $current->id)->exists())) {
            $previous = $this->cycles(collect([$current]), $current->first_due_date->copy(), Carbon::parse($terms[0]['start'])->subDay())->last();
            if (isset($previous['end']) && $previous['end']->gte(Carbon::parse($terms[0]['start']))) {
                throw ValidationException::withMessages(['exam_terms' => 'Start the new calendar after the previous term ends ('.$previous['end']->toDateString().').']);
            }
            $lastPaid = max($current->revenues()->whereNull('cancelled_at')->max('class_fee_due_date') ?? '',
                ClassFeePaymentHistory::where('class_fee_schedule_id', $current->id)->whereNull('cancelled_at')->max('class_fee_due_date') ?? '');
            if ($lastPaid >= $terms[0]['start']) {
                throw ValidationException::withMessages(['exam_terms' => 'The new calendar must start after prepaid terms.']);
            }
            $current->update(['ends_on' => Carbon::parse($terms[0]['start'])->subDay()->toDateString()]);
            $class->feeSchedules()->create(['fee_type' => 'exam', 'revenue_category_id' => $current->revenue_category_id,
                'amount' => $amount, 'first_due_date' => $terms[0]['start'], 'terms' => $terms]);

            return;
        }
        $target = $current;
        $split = Carbon::parse($terms[0]['start'])->year > $current->first_due_date->year;
        if ($split) {
            $target = $class->feeSchedules()->create(['fee_type' => 'exam', 'revenue_category_id' => $current->revenue_category_id,
                'amount' => $amount, 'first_due_date' => $terms[0]['start'], 'terms' => $terms]);
        }
        $receipts = $current->revenues()->get()->toBase()->concat(ClassFeePaymentHistory::where('class_fee_schedule_id', $current->id)->get());
        $oldCycles = $this->cycles(collect([$current]), $current->first_due_date->copy(),
            Carbon::parse($receipts->max('class_fee_due_date') ?: today())->endOfYear());
        $byYear = $oldCycles->values()->groupBy(fn ($cycle) => $cycle['due']->year);
        foreach ($receipts as $receipt) {
            $oldDate = $receipt->class_fee_due_date;
            if ($split && $oldDate->lt(Carbon::parse($terms[0]['start']))) {
                continue;
            }
            if ($current->terms) {
                $matched = $oldCycles->first(fn ($cycle) => $cycle['due']->isSameDay($oldDate));
                $oldNumber = $matched ? $matched['term'] - 1 : false;
                $yearOffset = $oldDate->year - Carbon::parse($current->terms[$oldNumber === false ? 0 : $oldNumber]['start'])->year;
            } else {
                $oldNumber = ($byYear[$oldDate->year] ?? collect())->search(fn ($cycle) => $cycle['due']->isSameDay($oldDate));
                $yearOffset = $oldDate->year - $current->first_due_date->year;
            }
            if ($oldNumber === false || $oldNumber > 2 || $yearOffset < 0) {
                if ($receipt->isCancelled()) {
                    continue;
                }
                throw ValidationException::withMessages(['exam_terms' => 'A fourth legacy installment has a receipt. Correct it before switching this class to three terms.']);
            }
            $yearOffset -= Carbon::parse($terms[0]['start'])->year - $current->first_due_date->year;
            $newDate = Carbon::parse($terms[$oldNumber]['start'])->addYearsNoOverflow($yearOffset)->toDateString();
            $receipt->update(['class_fee_due_date' => $newDate, 'class_fee_schedule_id' => $target->id]);
        }
        if ($split) {
            $current->update(['ends_on' => Carbon::parse($terms[0]['start'])->subDay()->toDateString()]);
        } else {
            $current->update(['amount' => $amount, 'first_due_date' => $terms[0]['start'], 'terms' => $terms]);
        }
    }

    public function formValues(ClassRoom $class): array
    {
        $values = [];
        foreach (ClassFeeSchedule::TYPES as $type => $label) {
            $rule = $class->feeSchedules->where('fee_type', $type)->whereNull('ends_on')->first();
            $values[$type.'_fee'] = $rule?->amount;
            $values[$type.'_fee_start_date'] = $rule?->first_due_date->toDateString();
            if ($type === 'exam') {
                $values['exam_terms'] = $rule?->terms;
            }
        }

        return $values;
    }

    public function examTermLabel(ClassFeeSchedule $schedule, Carbon $due, ?Collection $schedules = null): ?string
    {
        $number = $this->examTermNumber($schedule, $due, $schedules);

        return $number ? 'Term '.$number.' ('.$due->year.')' : null;
    }

    private function examTermNumber(ClassFeeSchedule $schedule, Carbon $due, ?Collection $schedules = null): ?int
    {
        if ($schedule->fee_type !== 'exam') {
            return null;
        }
        if ($schedule->terms) {
            $cycle = $this->cycles(collect([$schedule]), $due->copy(), $due->copy())->get($schedule->id.':'.$due->toDateString());
            $number = $cycle['term'] ?? null;
        } else {
            // Earlier quarterly receipts use their chronological term number within that calendar year.
            $schedules ??= $schedule->classRoom?->feeSchedules->where('fee_type', 'exam') ?? collect([$schedule]);
            $cycles = $this->cycles($schedules->where('fee_type', 'exam')->where('class_room_id', $schedule->class_room_id), $due->copy()->startOfYear(), $due->copy());
            $position = $cycles->keys()->search($schedule->id.':'.$due->toDateString());
            $number = $position === false ? null : $position + 1;
        }

        return $number;
    }

    /** Summarize the already filtered register rows, using their calculated due balances. */
    public function dueBreakdown(Collection $sections): array
    {
        $cards = [];
        foreach ([1, 2, 3] as $number) {
            $cards['term_'.$number] = ['label' => 'Due in Term '.$number, 'due' => 0.0, 'selected' => false];
        }
        $cards['facility'] = ['label' => 'Due in Facility Fees', 'due' => 0.0, 'selected' => false];

        foreach ($sections as $section) {
            foreach ($section['cycles'] as $cycle) {
                if ($cycle['schedule']->fee_type === 'facility') {
                    $key = 'facility';
                } else {
                    // Resolve against the full calendar so selecting only Term 3 never renumbers it.
                    $number = $cycle['term'] ?? $this->examTermNumber($cycle['schedule'], $cycle['due'], $section['schedules']);
                    $key = $number ? 'term_'.$number : 'other_exam';
                    // Preserve genuine legacy fourth charges rather than silently omitting their balances.
                    $cards[$key] ??= ['label' => $number ? 'Due in Term '.$number : 'Other Exam Fee Dues', 'due' => 0.0, 'selected' => false];
                }
                $cards[$key]['selected'] = true;
                $cards[$key]['due'] += (float) $section['rows']->sum(fn ($row) => $row['installments']->get($cycle['key'])['due'] ?? 0);
            }
        }

        $facility = $cards['facility'];
        unset($cards['facility']);
        ksort($cards, SORT_NATURAL);
        $cards['facility'] = $facility;

        return array_map(fn ($card) => array_replace($card, ['due' => round($card['due'], 2)]), $cards);
    }

    public function paymentLabel(ClassFeeSchedule $schedule, Carbon $due, ?Collection $schedules = null): string
    {
        $term = $this->examTermLabel($schedule, $due, $schedules);

        return $term ? 'Exam Fee — '.$term : ClassFeeSchedule::TYPES[$schedule->fee_type].' installment due '.$due->toDateString();
    }

    public function cycles(Collection $schedules, Carbon $from, Carbon $to): Collection
    {
        $cycles = collect();
        foreach ($schedules as $schedule) {
            if ($schedule->terms) {
                foreach ($schedule->terms as $number => $term) {
                    $start = Carbon::parse($term['start']);
                    $end = Carbon::parse($term['end']);
                    for ($year = max(0, $from->year - $start->year - 1); ; $year++) {
                        $due = $start->copy()->addYearsNoOverflow($year);
                        if ($due->gt($to) || ($schedule->ends_on && $due->gt($schedule->ends_on))) {
                            break;
                        }
                        if ($due->gte($from)) {
                            $key = $schedule->id.':'.$due->toDateString();
                            $cycles->put($key, ['key' => $key, 'schedule' => $schedule, 'due' => $due,
                                'end' => $end->copy()->addYearsNoOverflow($year), 'term' => $number + 1, 'amount' => (float) $schedule->amount]);
                        }
                    }
                }

                continue;
            }
            $anchor = $schedules->filter(fn ($rule) => $rule->class_room_id === $schedule->class_room_id && $rule->fee_type === $schedule->fee_type)
                ->min('first_due_date');
            $interval = $schedule->intervalMonths();
            $months = ($from->year - $anchor->year) * 12 + $from->month - $anchor->month;
            $index = max(0, intdiv(max(0, $months), $interval));
            while (true) {
                // Recalculate from the anchor so a month-end date never drifts.
                $due = $anchor->copy()->addMonthsNoOverflow($index * $interval);
                if ($due->gt($to) || ($schedule->ends_on && $due->gt($schedule->ends_on))) {
                    break;
                }
                if ($due->gte($from) && $due->gte($schedule->first_due_date)) {
                    $key = $schedule->id.':'.$due->toDateString();
                    $cycles->put($key, [
                        'key' => $key, 'schedule' => $schedule, 'due' => $due,
                        'amount' => (float) $schedule->amount,
                    ]);
                }
                $index++;
            }
        }

        return $cycles->sortBy(fn ($cycle) => $cycle['due']->timestamp);
    }

    public function eligible(Student $student, ClassFeeSchedule $schedule, Carbon $due, ?Carbon $termEnd = null): bool
    {
        $joined = $student->joining_date ?: $student->fee_start_date ?: $student->created_at;
        $joined = $joined ? Carbon::parse($joined)->startOfDay() : null;
        if ($schedule->fee_type === 'facility') {
            // Facility fees cover the calendar year, even for a December admission.
            if ($joined && $joined->year > $due->year) {
                return false;
            }
            $student->loadMissing(['facilityFeeRevenues', 'facilityFeeHistory']);
            $recorded = $student->facilityFeeRevenues->toBase()->concat($student->facilityFeeHistory)
                ->filter(fn ($payment) => $payment->class_fee_due_date?->year === $due->year)
                ->sortBy(fn ($payment) => [$payment->created_at?->timestamp ?? 0, $payment instanceof Revenue ? 0 : 1, $payment->id])
                ->first();
            if ($recorded) {
                // Preserve the original annual charge through edits, cancellations and transfers.
                return (int) $recorded->facility_fee_class_room_id === (int) $schedule->class_room_id;
            }
            $classDate = $due->copy()->startOfYear();
            if ($joined && $joined->gt($classDate)) {
                $classDate = $joined;
            }
        } else {
            // A new admission owes the term containing their joining date, including its last day.
            // Legacy installments without a term calendar retain their due-date rule.
            if ($joined && $joined->gt($termEnd ?? $due)) {
                return false;
            }
            $classDate = $joined && $joined->gt($due) ? $joined : $due;
        }
        $histories = $student->promotionHistories->sortBy(fn ($move) => [$move->created_at->timestamp, $move->id]);
        $lastMove = $histories->last(fn ($move) => $move->created_at->copy()->startOfDay()->lte($classDate));
        $classAtDue = $lastMove?->to_class_room_id;
        if (! $lastMove && $histories->isNotEmpty()) {
            $firstMove = $histories->first();
            $classAtDue = $firstMove->from_class_room_id;
            if ($schedule->fee_type === 'facility' || $termEnd !== null) {
                $classAtDue ??= $firstMove->to_class_room_id;
            }
        }
        $classAtDue ??= $student->class_room_id;

        return (int) $classAtDue === (int) $schedule->class_room_id;
    }

    public function paymentMap(Collection $schedules, Collection $students): Collection
    {
        if ($schedules->isEmpty() || $students->isEmpty()) {
            return collect();
        }

        $revenues = Revenue::query()->with('adjustments')
            ->whereIn('class_fee_schedule_id', $schedules->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))
            ->get();
        $history = ClassFeePaymentHistory::whereIn('class_fee_schedule_id', $schedules->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))->get();

        return $revenues->toBase()->concat($history)->groupBy(fn ($payment) => $payment->student_id.':'.$payment->class_fee_schedule_id.':'.$payment->class_fee_due_date?->toDateString());
    }

    public function status(Student $student, array $cycle, Collection $payments): array
    {
        $eligible = $this->eligible($student, $cycle['schedule'], $cycle['due'], $cycle['end'] ?? null);
        $paid = 0.0;
        $pending = 0.0;
        $waived = 0.0;
        foreach ($payments as $payment) {
            if ($payment->isCancelled()) {
                continue;
            }
            $net = max(0, (float) $payment->amount - (float) $payment->adjustments->where('type', 'refund')->sum('amount'));
            if (in_array($payment->payment_status, ['hold', 'pending'], true)) {
                $pending += $net;
            } elseif ($payment->payment_status === null || in_array($payment->payment_status, ['confirmed', 'paid'], true)) {
                $paid += $net;
                $waived += (float) $payment->adjustments->where('type', 'waiver')->sum('amount');
            }
        }
        $remaining = max(0, round($cycle['amount'] - $paid - $waived, 2));
        $upcoming = $cycle['due']->gt(today());
        $status = ! $eligible ? 'Not applicable' : ($remaining < 0.01 ? ($waived > 0 ? 'Waived / settled' : 'Paid') : ($pending > 0 ? 'Pending cheque' : ($paid > 0 || $waived > 0 ? 'Partial' : ($upcoming ? 'Upcoming' : (isset($cycle['end']) && today()->gt($cycle['end']) ? 'Overdue' : 'Unpaid')))));

        return [
            'eligible' => $eligible, 'status' => $status, 'paid' => round($paid, 2), 'pending' => round($pending, 2), 'waived' => round($waived, 2),
            'remaining' => $eligible ? $remaining : 0.0,
            'collectable' => $eligible ? max(0, round($remaining - $pending, 2)) : 0.0,
            'due' => $eligible && ! $upcoming ? $remaining : 0.0,
            'receipts' => $payments,
        ];
    }

    public function tracker(Collection $schedules, Collection $students, Carbon $from, Carbon $to, ?array $selectedExamDates = null): array
    {
        $cycles = $this->cycles($schedules, $from, $to)
            ->filter(fn ($cycle) => $cycle['schedule']->fee_type !== 'exam' || $selectedExamDates === null || in_array($cycle['due']->toDateString(), $selectedExamDates, true))
            ->sortBy(fn ($cycle) => [$cycle['schedule']->fee_type === 'exam' ? 0 : 1, $cycle['due']->timestamp]);
        $payments = $this->paymentMap($schedules, $students);
        if ($cycles->contains(fn ($cycle) => $cycle['schedule']->fee_type === 'facility')) {
            // Batch-load annual ownership records for the grid instead of querying per cell.
            (new \Illuminate\Database\Eloquent\Collection($students->all()))
                ->loadMissing(['facilityFeeRevenues', 'facilityFeeHistory']);
        }
        $rows = $students->map(function ($student) use ($cycles, $payments) {
            $statuses = $cycles->map(fn ($cycle, $key) => $this->status($student, $cycle, $payments->get($student->id.':'.$key, collect())));
            $dueStatuses = $statuses->filter(fn ($status, $key) => $status['eligible'] && $cycles[$key]['due']->lte(today()));

            return [
                'student' => $student, 'installments' => $statuses,
                'paid_count' => $dueStatuses->where('status', 'Paid')->count(),
                'waived_count' => $dueStatuses->where('status', 'Waived / settled')->count(),
                'due_count' => $dueStatuses->count(), 'due' => (float) $dueStatuses->sum('due'),
                'paid' => (float) $statuses->sum('paid'), 'pending' => (float) $statuses->sum('pending'),
            ];
        });

        return ['cycles' => $cycles, 'rows' => $rows, 'due' => (float) $rows->sum('due'), 'paid' => (float) $rows->sum('paid'),
            'unpaid_students' => $rows->where('due', '>', 0)->count()];
    }

    public function dashboard(string $type, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $schedules = ClassFeeSchedule::where('fee_type', $type)->get();
        $students = $schedules->isEmpty() ? collect() : Student::with('promotionHistories')->where('active', true)->where('alumni', false)->get();
        $due = 0.0;
        $unpaid = collect();
        foreach ($schedules->groupBy('class_room_id') as $classId => $rules) {
            $members = $students->filter(fn ($student) => (int) $student->class_room_id === (int) $classId
                || $student->promotionHistories->contains(fn ($move) => (int) $move->from_class_room_id === (int) $classId || (int) $move->to_class_room_id === (int) $classId));
            $from = $rules->min('first_due_date')->copy();
            $tracker = $this->tracker($rules, $members, $from, today());
            $due += $tracker['due'];
            $unpaid = $unpaid->merge($tracker['rows']->where('due', '>', 0)->pluck('student.id'));
        }
        $collected = Revenue::query()->whereHas('category', fn ($q) => $q->where('class_fee_type', $type))
            ->whereNull('cancelled_at')->where(fn ($q) => $q->whereNull('payment_status')->orWhereIn('payment_status', ['confirmed', 'paid']))
            ->whereDate('paid_at', '>=', $rangeStart->toDateString())->whereDate('paid_at', '<=', $rangeEnd->toDateString())
            ->withSum(['adjustments as refunded' => fn ($q) => $q->where('type', 'refund')], 'amount')->get()
            ->sum(fn ($payment) => max(0, (float) $payment->amount - (float) $payment->refunded));

        return ['collected' => round($collected, 2), 'due' => round($due, 2), 'unpaid_students' => $unpaid->unique()->count()];
    }
}
