<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\Student;
use App\Services\Billing\ClassFeePaymentService;
use App\Services\Billing\ClassFeeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

class ClassFeePaymentController extends Controller
{
    public function bulk(Request $request, ClassFeeService $fees)
    {
        $filters = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2099'],
            'class_room_id' => ['nullable', 'integer', 'exists:class_rooms,id'],
            'include_inactive' => ['nullable', 'boolean'],
        ]);
        $year = (int) ($filters['year'] ?? today()->year);
        $classes = ClassRoom::with('feeSchedules')->orderBy('level')->orderBy('name')->get();
        $students = Student::with('promotionHistories')
            ->when(! $request->boolean('include_inactive'), fn ($query) => $query->where('active', true)->where('alumni', false))
            ->orderBy('name')->get();
        $candidates = collect();
        foreach ($classes as $class) {
            $members = $students->filter(fn ($student) => (int) $student->class_room_id === $class->id
                || $student->promotionHistories->contains(fn ($move) => (int) $move->from_class_room_id === $class->id || (int) $move->to_class_room_id === $class->id));
            $tracker = $fees->tracker($class->feeSchedules, $members, Carbon::create($year, 1, 1), Carbon::create($year, 12, 31));
            foreach ($tracker['rows'] as $row) {
                $options = collect();
                foreach ($row['installments'] as $key => $status) {
                    if ($status['eligible'] && $status['collectable'] > 0) {
                        $cycle = $tracker['cycles'][$key];
                        $options->push([
                            'key' => $key, 'balance' => $status['collectable'], 'type' => $cycle['schedule']->fee_type,
                            'term' => $cycle['term'] ?? ($cycle['schedule']->fee_type === 'exam' ? $tracker['cycles']->filter(fn ($option) => $option['schedule']->fee_type === 'exam')->keys()->search($key) + 1 : null),
                            'label' => ($cycle['schedule']->fee_type === 'exam' ? (isset($cycle['term']) ? 'Term '.$cycle['term'].' · ' : 'Exam · ') : 'Facility · ')
                                .$cycle['due']->format('d M Y').(isset($cycle['end']) ? ' – '.$cycle['end']->format('d M Y') : ''),
                        ]);
                    }
                }
                if ($options->isNotEmpty()) {
                    $candidates->push(['key' => $class->id.':'.$row['student']->id, 'student_id' => $row['student']->id,
                        'name' => $row['student']->name, 'admission' => $row['student']->admission_number,
                        'class_id' => $class->id, 'class_name' => $class->name, 'installments' => $options->all()]);
                }
            }
        }

        return view('class-fees.bulk', compact('filters', 'year', 'classes', 'candidates') + ['submissionId' => (string) Str::uuid()]);
    }

    public function storeBulk(Request $request, ClassFeePaymentService $payments)
    {
        if ($request->has('lines_payload')) {
            try {
                $lines = json_decode($request->input('lines_payload'), true, 32, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                throw ValidationException::withMessages(['lines' => 'The payment list could not be read. Reload and try again.']);
            }
            $request->merge(['lines' => $lines]);
        }
        $data = $request->validate([
            'submission_id' => ['required', 'uuid'],
            'year' => ['required', 'integer', 'min:2000', 'max:2099'],
            'paid_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'payment_method' => ['required', 'in:cash,bank_transfer'],
            'bank_name' => ['required_if:payment_method,bank_transfer', 'nullable', 'string', 'max:120'],
            'bank_ref_no' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1', 'max:500'],
            'lines.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'lines.*.class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'lines.*.cycle' => ['required', 'string', 'regex:/^\d+:\d{4}-\d{2}-\d{2}$/'],
            'lines.*.amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'lines.*.record_revenue' => ['required', 'boolean'],
        ]);
        $keys = collect($data['lines'])->map(fn ($line) => $line['student_id'].':'.$line['cycle']);
        if ($keys->unique()->count() !== $keys->count()) {
            throw ValidationException::withMessages(['lines' => 'A student installment can only appear once in the payment list.']);
        }
        $hashData = $data;
        unset($hashData['submission_id']);
        $hash = hash('sha256', json_encode($hashData));
        DB::transaction(function () use ($data, $hash, $request, $payments) {
            $inserted = DB::table('class_fee_batches')->insertOrIgnore([
                'id' => $data['submission_id'], 'payload_hash' => $hash, 'created_by' => $request->user()->id,
                'payment_count' => count($data['lines']), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $batch = DB::table('class_fee_batches')->where('id', $data['submission_id'])->lockForUpdate()->first();
            if (! hash_equals($batch->payload_hash, $hash) || (int) $batch->created_by !== $request->user()->id) {
                throw ValidationException::withMessages(['submission_id' => 'This batch was already saved with different details. Open a new bulk payment list.']);
            }
            if (! $inserted) {
                return;
            }
            // Consistent lock order also protects a batch spanning several classes.
            $lines = collect($data['lines'])->sortBy(fn ($line) => sprintf('%020d:%020d:%s', $line['class_room_id'], $line['student_id'], $line['cycle']));
            ClassRoom::whereIn('id', $lines->pluck('class_room_id')->unique())->orderBy('id')->lockForUpdate()->get();
            foreach ($lines as $index => $line) {
                $class = ClassRoom::findOrFail($line['class_room_id']);
                $schedule = $class->feeSchedules()->find(explode(':', $line['cycle'], 2)[0]);
                if (! $schedule) {
                    throw ValidationException::withMessages(['lines.'.$index.'.cycle' => 'An installment does not belong to the selected class.']);
                }
                try {
                    $payments->collect($class, $schedule->fee_type, array_replace($data, $line, [
                        'submission_id' => (string) Uuid::uuid5(Uuid::NAMESPACE_URL, $data['submission_id'].':'.$index),
                    ]), $request->user());
                } catch (ValidationException $exception) {
                    $name = Student::find($line['student_id'])?->name ?? 'Student';
                    throw ValidationException::withMessages(['lines.'.$index => $name.': '.implode(' ', $exception->validator->errors()->all()).' No payments in this batch were saved.']);
                }
            }
        });

        return redirect()->route('class-fees.bulk', ['year' => $data['year']])
            ->with('status', count($data['lines']).' payments saved. Revenue and ledger include only the checked payments.');
    }

    public function edit(string $source, int $payment, ClassFeePaymentService $payments)
    {
        $item = $payments->find($source, $payment);
        abort_if($item->isCancelled(), 404);

        return view('class-fees.edit-payment', compact('source', 'item'));
    }

    public function update(Request $request, string $source, int $payment, ClassFeePaymentService $payments)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'paid_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'record_revenue' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer'],
            'bank_name' => ['required_if:payment_method,bank_transfer', 'nullable', 'string', 'max:120'],
            'bank_ref_no' => ['nullable', 'string', 'max:120'],
        ]);
        $item = $payments->correct($payments->find($source, $payment), $data, $request->user());

        return redirect()->route('class-fees.payments.edit', ['source' => $item instanceof Revenue ? 'revenue' : 'history', 'payment' => $item->id])
            ->with('status', 'Payment updated. Student balances, revenue, and ledger totals have been updated.');
    }

    public function destroy(Request $request, string $source, int $payment, ClassFeePaymentService $payments)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:1000']]);
        $item = $payments->find($source, $payment);
        $schedule = $item->classFeeSchedule;
        $payments->cancel($item, $data['reason'], $request->user());

        return redirect()->route('class-fees.index', ['class_room_id' => $schedule->class_room_id, 'year' => $item->class_fee_due_date->year])
            ->with('status', 'Payment deleted. The installment balance has been reopened and accounts updated.');
    }
}
