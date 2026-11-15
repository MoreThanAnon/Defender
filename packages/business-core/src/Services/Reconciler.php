<?php

namespace BusinessCore\Services;

use BusinessCore\Contracts\AuditRecorder;
use BusinessCore\Models\BankTransaction;
use BusinessCore\Models\PaymentTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Links a line on a bank statement to the payment task it settles. */
class Reconciler
{
    public function __construct(private AuditRecorder $audit)
    {
    }

    /** Open payment tasks for exactly this amount, best guess first. */
    public function suggestions(BankTransaction $tx): Collection
    {
        if ((float) $tx->amount >= 0 || $tx->payment_task_id) {
            return collect();
        }

        $needle = mb_strtolower($tx->description);

        return PaymentTask::withoutGlobalScope('business')
            ->where('business_id', $tx->business_id)
            ->whereIn('status', ['prepared', 'sent'])
            ->where('amount', number_format(abs((float) $tx->amount), 2, '.', ''))
            ->get()
            ->sortByDesc(function (PaymentTask $task) use ($needle) {
                $score = $task->status === 'sent' ? 1 : 0;
                if ($task->reference && str_contains($needle, mb_strtolower($task->reference))) {
                    $score += 4;
                }
                if (str_contains($needle, mb_strtolower($task->payee_name))) {
                    $score += 2;
                }

                return $score;
            })
            ->values();
    }

    public function match(BankTransaction $tx, PaymentTask $task): void
    {
        if ($tx->business_id !== $task->business_id) {
            throw new InvalidArgumentException('Transaction and payment task belong to different businesses.');
        }
        if ($tx->payment_task_id || ! $task->isOpen()) {
            throw new InvalidArgumentException('This transaction or payment task is already settled.');
        }
        if (abs((float) $tx->amount + (float) $task->amount) > 0.004) {
            throw new InvalidArgumentException('The amounts do not match. A payment task is only confirmed by a bank line for the same amount.');
        }

        DB::transaction(function () use ($tx, $task) {
            $tx->payment_task_id = $task->id;
            $tx->project_id ??= $task->project_id;
            $tx->category ??= 'Supplier payment';
            $tx->refreshStatus();
            $tx->reviewed_by = request()->user()?->getKey();
            $tx->reviewed_at = now();
            $tx->save();

            $task->update(['status' => 'reconciled', 'reconciled_at' => now()]);

            $this->audit->record('payment.reconciled', $task, ['transaction_id' => $tx->id, 'amount' => $task->amount]);
        });
    }

    public function unmatch(BankTransaction $tx): void
    {
        if (! $task = $tx->paymentTask) {
            return;
        }

        DB::transaction(function () use ($tx, $task) {
            $tx->payment_task_id = null;
            $tx->refreshStatus();
            $tx->save();
            $task->update(['status' => $task->sent_at ? 'sent' : 'prepared', 'reconciled_at' => null]);
            $this->audit->record('payment.unreconciled', $task, ['transaction_id' => $tx->id]);
        });
    }
}
