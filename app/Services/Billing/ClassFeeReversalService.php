<?php

namespace App\Services\Billing;

use App\Models\ClassFeePaymentHistory;
use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassFeeReversalService
{
    public function __construct(private ClassFeeService $fees, private ClassFeePaymentService $payments, private AuditLogger $audit) {}

    /** Keep the reviewed receipt set stable, including pending holds on a partly paid fee. */
    public function preview(Collection $receipts): array
    {
        $active = $receipts->reject(fn ($payment) => $payment->isCancelled())->sortBy(fn ($payment) => ($payment instanceof Revenue ? 'revenue:' : 'history:').sprintf('%020d', $payment->id))->values();
        $details = [];
        $snapshots = [];
        $blocked = null;
        foreach ($active as $payment) {
            $revenue = $payment instanceof Revenue;
            if ($revenue && $payment->adjustments->isNotEmpty()) {
                $blocked = 'A receipt has refunds or waivers. Review its adjustments in Revenue first.';
            } elseif ($revenue && ! in_array($payment->payment_status, [null, 'confirmed', 'paid', 'hold', 'pending'], true)) {
                $blocked ??= 'A cheque requires individual review in Revenue before this fee can be made unpaid.';
            }
            $pending = in_array($payment->payment_status, ['hold', 'pending'], true);
            $details[] = ['source' => $revenue ? 'revenue' : 'history', 'id' => $payment->id,
                'reference' => $revenue ? ($payment->bill_no ?: 'Receipt #'.$payment->id) : 'History #'.$payment->id,
                'amount' => (float) $payment->amount, 'date' => $payment->paid_at->toDateString(),
                'pending' => $pending, 'accounting' => $pending ? 'Pending cheque' : ($revenue ? 'Revenue & ledger' : 'History only')];
            $snapshots[] = ['source' => $revenue ? 'revenue' : 'history', 'attributes' => $payment->getAttributes(),
                'adjustments' => $payment->adjustments->sortBy('id')->map(fn ($adjustment) => $adjustment->getAttributes())->values()->all()];
        }

        return ['fingerprint' => hash('sha256', json_encode($snapshots, JSON_THROW_ON_ERROR)),
            'payments' => $details, 'blocked_reason' => $blocked,
            'revenue' => round((float) collect($details)->where('source', 'revenue')->where('pending', false)->sum('amount'), 2),
            'history' => round((float) collect($details)->where('source', 'history')->sum('amount'), 2),
            'pending' => round((float) collect($details)->where('pending', true)->sum('amount'), 2)];
    }

    public function makeUnpaid(array $data, User $user): void
    {
        $hashData = ['action' => 'make_unpaid', 'year' => $data['year'], 'reason' => $data['reason'], 'lines' => $data['lines']];
        $hash = hash('sha256', json_encode($hashData, JSON_THROW_ON_ERROR));
        DB::transaction(function () use ($data, $hash, $user) {
            $inserted = DB::table('class_fee_batches')->insertOrIgnore([
                'id' => $data['submission_id'], 'payload_hash' => $hash, 'created_by' => $user->id,
                'payment_count' => count($data['lines']), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $batch = DB::table('class_fee_batches')->where('id', $data['submission_id'])->lockForUpdate()->first();
            if (! $batch || ! hash_equals($batch->payload_hash, $hash) || (int) $batch->created_by !== $user->id) {
                throw ValidationException::withMessages(['submission_id' => 'This batch was already used with different details. Reload the Make unpaid page.']);
            }
            if (! $inserted) {
                return;
            }
            $lines = collect($data['lines'])->sortBy(fn ($line) => sprintf('%020d:%020d:%s', $line['class_room_id'], $line['student_id'], $line['cycle']));
            ClassRoom::whereIn('id', $lines->pluck('class_room_id')->unique())->orderBy('id')->lockForUpdate()->get();
            Student::whereIn('id', $lines->pluck('student_id')->unique())->orderBy('id')->lockForUpdate()->get();
            $reversed = 0;
            foreach ($lines as $index => $line) {
                try {
                    $this->reverseLine($line, (int) $data['year'], $data['reason'], $user, $reversed);
                } catch (ValidationException $exception) {
                    $name = Student::find($line['student_id'])?->name ?? 'Student';
                    throw ValidationException::withMessages(['lines.'.$index => $name.': '.implode(' ', $exception->validator->errors()->all()).' No payments in this batch were reversed.']);
                }
            }
            $this->audit->log('class_fee.bulk_made_unpaid', null, $data['reason'], [
                'batch_id' => $data['submission_id'], 'year' => $data['year'], 'fees_reopened' => count($data['lines']),
                'payments_cancelled' => $reversed, 'reason' => $data['reason'],
            ]);
        });
    }

    private function reverseLine(array $line, int $year, string $reason, User $user, int &$reversed): void
    {
        [$scheduleId, $date] = explode(':', $line['cycle'], 2);
        $class = ClassRoom::findOrFail($line['class_room_id']);
        $schedule = $class->feeSchedules()->find($scheduleId);
        try {
            $due = Carbon::createFromFormat('!Y-m-d', $date);
        } catch (\Throwable) {
            $due = null;
        }
        if (! $schedule || ! $due || $due->toDateString() !== $date || $due->year !== $year
            || ! $this->fees->cycles($class->feeSchedules, $due->copy(), $due->copy())->has($line['cycle'])) {
            throw ValidationException::withMessages(['cycle' => 'Choose a valid fee for the selected class and year.']);
        }
        $query = fn ($query) => $query->where('student_id', $line['student_id'])->where('class_fee_schedule_id', $schedule->id)->whereDate('class_fee_due_date', $date)->lockForUpdate();
        $receipts = $query(Revenue::with('adjustments'))->get()->toBase()
            ->concat($query(ClassFeePaymentHistory::query())->get());
        $preview = $this->preview($receipts);
        if (! hash_equals($preview['fingerprint'], $line['fingerprint'])) {
            throw ValidationException::withMessages(['payment' => 'Payments changed after the list was loaded. Reload and review them again.']);
        }
        if ($preview['blocked_reason']) {
            throw ValidationException::withMessages(['payment' => $preview['blocked_reason']]);
        }
        if ($preview['revenue'] + $preview['history'] <= 0) {
            throw ValidationException::withMessages(['payment' => 'This fee has no paid amount to reverse.']);
        }
        foreach ($receipts->reject(fn ($payment) => $payment->isCancelled()) as $payment) {
            $this->payments->cancel($payment, $reason, $user);
            $reversed++;
        }
    }
}
