<?php

namespace App\Services\Billing;

use App\Models\ClassFeePaymentHistory;
use App\Models\ClassFeeSchedule;
use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClassFeePaymentService
{
    public function __construct(private ClassFeeService $fees, private BillNumberService $billNumbers, private AuditLogger $audit) {}

    public function collect(ClassRoom $classroom, string $type, array $data, User $user): Revenue|ClassFeePaymentHistory
    {
        return DB::transaction(function () use ($classroom, $type, $data, $user) {
            ClassRoom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            $duplicate = ClassFeePaymentHistory::where('class_fee_submission_id', $data['submission_id'])->first()
                ?? Revenue::where('class_fee_submission_id', $data['submission_id'])->first();
            if ($duplicate) {
                if ((int) $duplicate->student_id !== (int) $data['student_id']
                    || $duplicate->class_fee_schedule_id.':'.$duplicate->class_fee_due_date->toDateString() !== $data['cycle']
                    || (int) $duplicate->classFeeSchedule->class_room_id !== (int) $classroom->id
                    || (isset($data['amount']) && ! $duplicate->isCancelled() && round((float) $duplicate->amount, 2) !== round((float) $data['amount'], 2))) {
                    throw ValidationException::withMessages(['submission_id' => 'This submission was already used for a different payment.']);
                }

                return $duplicate;
            }
            $student = Student::with('promotionHistories')->lockForUpdate()->findOrFail($data['student_id']);
            $schedules = $classroom->feeSchedules()->where('fee_type', $type)->get();
            $dueDate = explode(':', $data['cycle'], 2)[1];
            try {
                $due = Carbon::createFromFormat('!Y-m-d', $dueDate);
            } catch (\Throwable) {
                throw ValidationException::withMessages(['cycle' => 'Choose a valid installment.']);
            }
            $cycle = $this->fees->cycles($schedules, $due->copy(), $due->copy())->get($data['cycle']);
            if (! $cycle || ! $this->fees->eligible($student, $cycle['schedule'], $due)) {
                throw ValidationException::withMessages(['cycle' => 'This installment does not apply to this student.']);
            }
            $payments = $this->fees->paymentMap($schedules, collect([$student]));
            $status = $this->fees->status($student, $cycle, $payments->get($student->id.':'.$data['cycle'], collect()));
            $existing = ! empty($data['existing_revenue_id']) ? Revenue::with('adjustments')->lockForUpdate()->findOrFail($data['existing_revenue_id']) : null;
            if ($existing && ((int) $existing->student_id !== (int) $student->id || (int) $existing->revenue_category_id !== (int) $cycle['schedule']->revenue_category_id
                || $existing->class_fee_schedule_id || $existing->isCancelled() || ! in_array($existing->payment_status, [null, 'confirmed', 'paid', 'hold', 'pending'], true))) {
                throw ValidationException::withMessages(['existing_revenue_id' => 'Choose an unassigned receipt for this student and fee.']);
            }
            $amount = $existing ? max(0, (float) $existing->amount - (float) $existing->adjustments->where('type', 'refund')->sum('amount')) : (float) $data['amount'];
            if ($amount <= 0 || $amount > $status['collectable']) {
                throw ValidationException::withMessages(['amount' => 'Amount exceeds the remaining installment balance (Rs '.number_format($status['collectable'], 2).'). Pending cheques reserve their amount.']);
            }
            $tag = [
                'class_fee_schedule_id' => $cycle['schedule']->id, 'class_fee_due_date' => $dueDate,
                'class_fee_submission_id' => $data['submission_id'],
            ];
            if ($existing) {
                $existing->update($tag);
                $revenue = $existing;
            } elseif (! ($data['record_revenue'] ?? true)) {
                $revenue = ClassFeePaymentHistory::create($tag + [
                    'student_id' => $student->id, 'amount' => $amount, 'paid_at' => $data['paid_at'],
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'notes' => $data['notes'] ?? null, 'created_by' => $user->id,
                ]);
            } else {
                $method = $data['payment_method'] ?? 'cash';
                $meta = match ($method) {
                    'bank_transfer' => ['bank' => $data['bank_name'], 'ref_no' => $data['bank_ref_no'] ?? null],
                    'cheque' => ['bank' => $data['cheque_bank'], 'cheque_number' => $data['cheque_number']],
                    default => null,
                };
                $revenue = Revenue::create($tag + [
                    'bill_no' => $this->billNumbers->nextRevenueBillNumber(),
                    'student_id' => $student->id, 'revenue_category_id' => $cycle['schedule']->revenue_category_id,
                    'amount' => $amount, 'paid_at' => $data['paid_at'], 'payment_method' => $method,
                    'payment_status' => $method === 'cheque' ? 'hold' : 'confirmed',
                    'confirmed_at' => $method === 'cheque' ? null : now(),
                    'payment_meta' => $meta, 'cheque_date' => $method === 'cheque' ? $data['cheque_date'] : null,
                    'notes' => ClassFeeSchedule::TYPES[$type].' installment due '.$dueDate.(! empty($data['notes']) ? ' — '.$data['notes'] : ''),
                    'created_by' => $user->id,
                ]);
            }
            $this->audit->log($existing ? 'class_fee.receipt_linked' : 'class_fee.collected', $revenue, ClassFeeSchedule::TYPES[$type].' installment recorded', [
                'class_room_id' => $classroom->id, 'student_id' => $student->id, 'due_date' => $dueDate, 'amount' => $amount,
            ]);

            return $revenue;
        });
    }

    public function find(string $source, int $id): Revenue|ClassFeePaymentHistory
    {
        abort_unless(in_array($source, ['revenue', 'history'], true), 404);
        $payment = ($source === 'revenue' ? Revenue::query() : ClassFeePaymentHistory::query())->findOrFail($id);
        abort_unless($payment->class_fee_schedule_id, 404);

        return $payment;
    }

    public function correct(Revenue|ClassFeePaymentHistory $payment, array $data, User $user): Revenue|ClassFeePaymentHistory
    {
        return DB::transaction(function () use ($payment, $data, $user) {
            ClassRoom::whereKey($payment->classFeeSchedule->class_room_id)->lockForUpdate()->firstOrFail();
            $payment = $payment->newQuery()->lockForUpdate()->findOrFail($payment->id);
            $this->assertEditable($payment);
            $schedule = $payment->classFeeSchedule;
            $student = Student::with('promotionHistories')->lockForUpdate()->findOrFail($payment->student_id);
            $due = $payment->class_fee_due_date;
            $key = $schedule->id.':'.$due->toDateString();
            $cycle = $this->fees->cycles(collect([$schedule]), $due->copy(), $due->copy())->get($key);
            if (! $cycle) {
                throw ValidationException::withMessages(['amount' => 'The original installment is no longer available.']);
            }
            $others = $this->fees->paymentMap(collect([$schedule]), collect([$student]))->get($student->id.':'.$key, collect())
                ->reject(fn ($other) => get_class($other) === get_class($payment) && $other->id === $payment->id);
            $status = $this->fees->status($student, $cycle, $others);
            if ((float) $data['amount'] > $status['collectable']) {
                throw ValidationException::withMessages(['amount' => 'Amount exceeds the remaining balance (Rs '.number_format($status['collectable'], 2).').']);
            }
            $before = $payment->only(['amount', 'paid_at', 'notes']);
            $isRevenue = $payment instanceof Revenue;
            $attributes = ['amount' => $data['amount'], 'paid_at' => $data['paid_at'], 'notes' => $data['notes'] ?? null];
            if ($isRevenue === (bool) $data['record_revenue']) {
                $payment->update($attributes);
                $this->audit->log('class_fee.payment_updated', $payment, 'Fee payment corrected', ['before' => $before, 'after' => $attributes]);

                return $payment;
            }
            // Retain the old record for audit, and move the settlement to the other source.
            $newData = $attributes + [
                'submission_id' => (string) Str::uuid(), 'student_id' => $payment->student_id,
                'cycle' => $key, 'record_revenue' => (bool) $data['record_revenue'],
                'payment_method' => $payment->payment_method ?: 'cash',
            ];
            if ($newData['record_revenue']) {
                // A historical entry represents a completed payment, not a new pending cheque.
                $newData['payment_method'] = $data['payment_method'] ?? 'cash';
                $newData['bank_name'] = $data['bank_name'] ?? null;
                $newData['bank_ref_no'] = $data['bank_ref_no'] ?? null;
            }
            $this->cancel($payment, 'Changed revenue and ledger inclusion', $user);
            $replacement = $this->collect($schedule->classRoom, $schedule->fee_type, $newData, $user);
            $this->audit->log('class_fee.accounting_changed', $replacement, 'Fee accounting inclusion changed', [
                'original_source' => $isRevenue ? 'revenue' : 'history', 'original_id' => $payment->id, 'before' => $before,
                'record_revenue' => (bool) $data['record_revenue'],
            ]);

            return $replacement;
        });
    }

    private function assertEditable(Revenue|ClassFeePaymentHistory $payment): void
    {
        if ($payment->isCancelled()) {
            throw ValidationException::withMessages(['payment' => 'This payment has already been deleted or cancelled.']);
        }
        if ($payment instanceof Revenue && $payment->adjustments()->exists()) {
            throw ValidationException::withMessages(['payment' => 'This receipt has refunds or waivers. Correct those adjustments in Revenue before editing or deleting the payment.']);
        }
        if ($payment instanceof Revenue && ! in_array($payment->payment_status, [null, 'confirmed', 'paid', 'hold', 'pending'], true)) {
            throw ValidationException::withMessages(['payment' => 'Resolve this cheque in Revenue before editing it.']);
        }
    }

    public function cancel(Revenue|ClassFeePaymentHistory $payment, string $reason, User $user): void
    {
        DB::transaction(function () use ($payment, $reason, $user) {
            ClassRoom::whereKey($payment->classFeeSchedule->class_room_id)->lockForUpdate()->firstOrFail();
            $payment = $payment->newQuery()->lockForUpdate()->findOrFail($payment->id);
            $this->assertEditable($payment);
            $before = $payment->toArray();
            if ($payment instanceof Revenue) {
                $payment->update(['amount' => 0, 'payment_status' => 'cancelled', 'confirmed_at' => null,
                    'cancelled_at' => now(), 'cancelled_by' => $user->id, 'cancel_reason' => $reason]);
            } else {
                $payment->update(['cancelled_at' => now()]);
            }
            $this->audit->log('class_fee.payment_deleted', $payment, $reason, ['before' => $before]);
        });
    }
}
