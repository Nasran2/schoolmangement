<?php

namespace App\Console\Commands;

use App\Models\Revenue;
use App\Models\RevenueCategory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncSeminarRevenue extends Command
{
    protected $signature = 'seminars:sync-revenue {--dry-run : Show what would change without writing to DB}';

    protected $description = 'Backfill/link revenue records for seminar student payments (paid seminar_students)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $category = RevenueCategory::firstOrCreate(
            ['name' => 'Seminar Fee'],
            [
                'payment_type' => 'seminar',
                'description' => 'Seminar student fee collections',
                'active' => true,
            ]
        );

        $created = 0;
        $linkedExisting = 0;
        $removedOrphan = 0;

        $rows = DB::table('seminar_students')
            ->join('seminars', 'seminars.id', '=', 'seminar_students.seminar_id')
            ->select([
                'seminar_students.id as ss_id',
                'seminar_students.student_id',
                'seminar_students.seminar_id',
                'seminar_students.paid',
                'seminar_students.amount as ss_amount',
                'seminar_students.paid_at as ss_paid_at',
                'seminar_students.revenue_id',
                'seminars.name as seminar_name',
                'seminars.fee_per_student as seminar_fee',
                'seminars.date as seminar_date',
            ])
            ->orderBy('seminar_students.id')
            ->get();

        DB::beginTransaction();
        try {
            foreach ($rows as $r) {
                $paid = (bool) $r->paid;

                $amount = (float) ($r->ss_amount ?? $r->seminar_fee ?? 0);
                $paidAt = $r->ss_paid_at
                    ? Carbon::parse($r->ss_paid_at)->toDateString()
                    : (Carbon::parse($r->seminar_date)->toDateString());

                // If marked unpaid but has linked revenue_id, delete & unlink.
                if (! $paid && ! empty($r->revenue_id)) {
                    $rev = Revenue::query()->find((int) $r->revenue_id);
                    if ($rev) {
                        $this->line("Unlink+delete revenue {$rev->id} for seminar_student {$r->ss_id}");
                        if (! $dryRun) {
                            $rev->delete();
                        }
                    }
                    if (! $dryRun) {
                        DB::table('seminar_students')->where('id', (int) $r->ss_id)->update(['revenue_id' => null]);
                    }
                    $removedOrphan++;
                    continue;
                }

                // Paid but missing revenue link: create or link.
                if ($paid && empty($r->revenue_id)) {
                    if ($amount <= 0) {
                        $this->warn("Skip seminar_student {$r->ss_id}: amount is 0");
                        continue;
                    }

                    $notes = "Seminar {$r->seminar_name} fee";

                    $existing = Revenue::query()
                        ->where('revenue_category_id', $category->id)
                        ->where('student_id', (int) $r->student_id)
                        ->whereDate('paid_at', $paidAt)
                        ->where('amount', $amount)
                        ->where('notes', $notes)
                        ->orderByDesc('id')
                        ->first();

                    if ($existing) {
                        $this->line("Link existing revenue {$existing->id} -> seminar_student {$r->ss_id}");
                        if (! $dryRun) {
                            DB::table('seminar_students')->where('id', (int) $r->ss_id)->update(['revenue_id' => $existing->id]);
                        }
                        $linkedExisting++;
                        continue;
                    }

                    $this->line("Create revenue for seminar_student {$r->ss_id} ({$amount} on {$paidAt})");
                    if (! $dryRun) {
                        $rev = Revenue::create([
                            'bill_no' => null,
                            'revenue_category_id' => $category->id,
                            'student_id' => (int) $r->student_id,
                            'amount' => $amount,
                            'paid_at' => $paidAt,
                            'notes' => $notes,
                            'created_by' => null,
                        ]);

                        DB::table('seminar_students')->where('id', (int) $r->ss_id)->update(['revenue_id' => $rev->id]);
                    }
                    $created++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->info('Done.');
        $this->line("Created: {$created}");
        $this->line("Linked existing: {$linkedExisting}");
        $this->line("Removed orphan links: {$removedOrphan}");

        if ($dryRun) {
            $this->warn('Dry-run mode: no changes were written.');
        }

        return Command::SUCCESS;
    }
}
