<?php

namespace App\Services\Billing;

use App\Models\ClassRoom;
use App\Models\Revenue;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

/** One collection and printed bill, with separate exam/computer income and balances. */
class ClassFeeCombinedPaymentService
{
    public function __construct(private ClassFeeService $fees, private ClassFeePaymentService $payments, private AuditLogger $audit) {}

    public function collect(ClassRoom $class, string $type, array $data, User $user): Revenue
    {
        if ($type !== 'exam' || ! ($data['record_revenue'] ?? true) || ! empty($data['existing_revenue_id'])) {
            throw ValidationException::withMessages(['include_computer' => 'Combined collection requires a new exam and computer revenue payment. Use bulk entry for historical payments.']);
        }
        $details = collect($data)->only(['student_id', 'cycle', 'amount', 'paid_at', 'payment_method', 'bank_name', 'bank_ref_no',
            'cheque_date', 'cheque_number', 'cheque_bank', 'cheque_student_name', 'bill_no', 'notes', 'computer_cycle', 'computer_amount'])->all();
        $hash = hash('sha256', json_encode(['action' => 'exam_computer', 'class' => $class->id, 'details' => $details], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($class, $data, $user, $hash) {
            $inserted = DB::table('class_fee_batches')->insertOrIgnore(['id' => $data['submission_id'], 'payload_hash' => $hash,
                'created_by' => $user->id, 'payment_count' => 2, 'created_at' => now(), 'updated_at' => now()]);
            $batch = DB::table('class_fee_batches')->where('id', $data['submission_id'])->lockForUpdate()->first();
            if (! $batch || ! hash_equals($batch->payload_hash, $hash) || (int) $batch->created_by !== (int) $user->id) {
                throw ValidationException::withMessages(['submission_id' => 'This submission was already used with different payment details. Reload before collecting again.']);
            }
            $examId = (string) Uuid::uuid5(Uuid::NAMESPACE_URL, $data['submission_id'].':exam');
            if (! $inserted) {
                return Revenue::where('class_fee_submission_id', $examId)->firstOrFail();
            }
            ClassRoom::whereKey($class->id)->lockForUpdate()->firstOrFail();
            $student = Student::with('promotionHistories')->lockForUpdate()->findOrFail($data['student_id']);
            $due = Carbon::parse(explode(':', $data['cycle'], 2)[1]);
            $exam = $this->fees->cycles($class->feeSchedules()->where('fee_type', 'exam')->get(), $due->copy(), $due->copy())->get($data['cycle']);
            $computer = $exam ? $this->fees->computerForTerm($class, $student, $exam) : null;
            if (! $computer || $computer['cycle']['key'] !== $data['computer_cycle']) {
                throw ValidationException::withMessages(['computer_cycle' => 'The computer fee must apply to the same class and term and have a remaining balance. Reload the student’s fees.']);
            }
            if ((float) $data['computer_amount'] > $computer['status']['collectable']) {
                throw ValidationException::withMessages(['computer_amount' => 'The computer amount exceeds its remaining balance (Rs '.number_format($computer['status']['collectable'], 2).'). Reload the fees.']);
            }
            $examReceipt = $this->payments->collect($class, 'exam', array_replace($data, ['submission_id' => $examId, 'record_revenue' => true]), $user);
            // Reserve one numbered bill; the separate computer accounting line has a unique component reference.
            $componentBill = $examReceipt->bill_no.'-C';
            if (strlen($componentBill) > 255 || Revenue::where('bill_no', $componentBill)->exists()) {
                throw ValidationException::withMessages(['bill_no' => 'The computer component reference is already used. Choose another bill number.']);
            }
            $computerReceipt = $this->payments->collect($class, 'computer', array_replace($data, [
                'submission_id' => (string) Uuid::uuid5(Uuid::NAMESPACE_URL, $data['submission_id'].':computer'),
                'cycle' => $data['computer_cycle'], 'amount' => $data['computer_amount'], 'record_revenue' => true,
                '_combined_bill_number' => $componentBill,
            ]), $user);
            foreach ([$examReceipt, $computerReceipt] as $receipt) {
                $meta = $receipt->payment_meta ?? [];
                $meta['fee_bundle'] = ['id' => $data['submission_id'], 'primary_id' => $examReceipt->id,
                    'bill_no' => $examReceipt->bill_no, 'original_amount' => (float) $receipt->amount];
                $receipt->update(['payment_meta' => $meta]);
            }
            $this->audit->log('class_fee.combined_collected', $examReceipt, 'Exam and computer fees collected together', [
                'computer_revenue_id' => $computerReceipt->id, 'bill_no' => $examReceipt->bill_no,
                'total' => round((float) $examReceipt->amount + (float) $computerReceipt->amount, 2),
            ]);

            return $examReceipt;
        });
    }

    public function receiptItems(Revenue $item, bool $lock = false): Collection
    {
        $group = $item->payment_meta['fee_bundle']['id'] ?? null;
        if (! $group) {
            return collect([$lock ? Revenue::whereKey($item->id)->lockForUpdate()->firstOrFail() : $item]);
        }

        return Revenue::with(['student.classRoom', 'category', 'classFeeSchedule.classRoom', 'adjustments'])
            ->where('payment_meta->fee_bundle->id', $group)->where('student_id', $item->student_id)
            ->where('created_by', $item->created_by)->orderBy('id')->when($lock, fn ($query) => $query->lockForUpdate())->get();
    }

    /** All accounting lines for one physical cheque change together. Cancelled lines remain cancelled. */
    public function resolveCheque(Revenue $item, string $status, Carbon $at): void
    {
        DB::transaction(function () use ($item, $status, $at) {
            if ($item->classFeeSchedule) {
                ClassRoom::whereKey($item->classFeeSchedule->class_room_id)->lockForUpdate()->firstOrFail();
            }
            $items = $this->receiptItems($item, true)->reject(fn ($receipt) => $receipt->isCancelled());
            if ($items->isEmpty() || $items->contains(fn ($receipt) => $receipt->payment_method !== 'cheque'
                || ! in_array($receipt->payment_status, ['hold', 'pending'], true))) {
                throw ValidationException::withMessages(['cheque' => 'This cheque has already changed. Reload its current status.']);
            }
            if ($status === 'confirmed' && $items->contains(fn ($receipt) => $receipt->cheque_date && $receipt->cheque_date->gt($at->copy()->endOfDay()))) {
                throw ValidationException::withMessages(['passed_date' => 'The clearance date must be on or after every cheque line date.']);
            }
            foreach ($items as $receipt) {
                $receipt->update(['payment_status' => $status, 'confirmed_at' => $at]);
                $this->audit->log('class_fee.cheque_'.$status, $receipt, 'Combined fee cheque status updated', ['payment_status' => $status]);
            }
        });
    }
}
