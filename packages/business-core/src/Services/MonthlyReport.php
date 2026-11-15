<?php

namespace BusinessCore\Services;

use BusinessCore\Models\BankTransaction;
use BusinessCore\Models\Business;
use BusinessCore\Models\PaymentTask;
use BusinessCore\Models\Project;
use BusinessCore\Models\Proposal;
use BusinessCore\Models\ReportSignoff;
use BusinessCore\Models\StatementImport;
use BusinessCore\Models\Task;
use Illuminate\Support\Carbon;

/** Builds the monthly management report from imported statements and workflow records. */
class MonthlyReport
{
    public function build(Business $business, string $period): array
    {
        $start = Carbon::createFromFormat('!Y-m', $period)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $inMonth = fn () => BankTransaction::query()->where('business_id', $business->id)
            ->whereBetween('booked_on', [$start->toDateString(), $end->toDateString()]);
        $ordered = fn ($q, string $dir) => $q->orderBy('booked_on', $dir)->orderBy('statement_import_id', $dir)->orderBy('row_number', $dir);

        $before = $ordered(BankTransaction::query()->where('business_id', $business->id)
            ->where('booked_on', '<', $start->toDateString())->whereNotNull('balance_after'), 'desc')->first();
        $last = $ordered($inMonth()->whereNotNull('balance_after'), 'desc')->first();
        $first = $ordered($inMonth()->whereNotNull('balance_after'), 'asc')->first();

        // Opening cash: the statement balance carried in, else worked back from the first line of the month.
        $opening = $before?->balance_after
            ?? ($first ? number_format((float) $first->balance_after - (float) $first->amount, 2, '.', '') : null);

        $transactions = $ordered($inMonth()->with(['project', 'paymentTask']), 'asc')->get();

        $categories = $transactions->groupBy(fn ($t) => $t->category ?: 'Uncategorised')
            ->map(fn ($group) => [
                'in' => (float) $group->where('amount', '>', 0)->sum('amount'),
                'out' => -1 * (float) $group->where('amount', '<', 0)->sum('amount'),
                'count' => $group->count(),
            ])->sortKeys();

        $projects = Project::query()->where('business_id', $business->id)->orderBy('name')->get()->map(fn (Project $p) => [
            'project' => $p,
            'budget' => $p->budget_amount === null ? null : (float) $p->budget_amount,
            'paid' => $p->paid(),
            'paid_in_month' => -1 * (float) $transactions->where('project_id', $p->id)->where('amount', '<', 0)->sum('amount'),
            'committed' => $p->committed(),
        ]);

        return [
            'business' => $business,
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'opening' => $opening,
            'closing' => $last?->balance_after,
            'receipts' => (float) $transactions->where('amount', '>', 0)->sum('amount'),
            'payments' => -1 * (float) $transactions->where('amount', '<', 0)->sum('amount'),
            'transactions' => $transactions,
            'categories' => $categories,
            'unreviewed' => $transactions->whereIn('status', ['unreviewed', 'flagged'])->count(),
            'projects' => $projects,
            'pending_payments' => PaymentTask::query()->where('business_id', $business->id)->whereIn('status', ['prepared', 'sent'])->with('supplier')->get(),
            'overdue_tasks' => Task::query()->where('business_id', $business->id)->where('status', '!=', 'done')->whereDate('due_date', '<', today())->get(),
            'decisions' => Proposal::query()->where('business_id', $business->id)
                ->whereBetween('decided_at', [$start, $end])->with('decider')->get(),
            'imports' => StatementImport::query()->where('business_id', $business->id)
                ->whereDate('period_start', '<=', $end)->whereDate('period_end', '>=', $start)->get(),
            'signoff' => ReportSignoff::query()->where('business_id', $business->id)->where('period', $period)->with('signer')->first(),
            'generated_at' => now(),
        ];
    }
}
