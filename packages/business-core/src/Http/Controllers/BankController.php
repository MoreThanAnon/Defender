<?php

namespace BusinessCore\Http\Controllers;

use BusinessCore\Models\BankTransaction;
use BusinessCore\Models\PaymentTask;
use BusinessCore\Models\Project;
use BusinessCore\Models\StatementImport;
use BusinessCore\Services\Reconciler;
use BusinessCore\Services\StatementImporter;
use BusinessCore\Support\ImportException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class BankController extends Controller
{
    public function index(Request $request, Reconciler $reconciler)
    {
        $this->allow('reports');

        $filter = $request->query('show', 'all');
        $transactions = BankTransaction::with(['project', 'paymentTask'])
            ->when($filter === 'todo', fn ($q) => $q->whereIn('status', ['unreviewed', 'flagged']))
            ->when($request->filled('q'), fn ($q) => $q->where('description', 'like', '%'.$request->query('q').'%'))
            ->orderByDesc('booked_on')->orderByDesc('statement_import_id')->orderByDesc('row_number')
            ->paginate(40)->withQueryString();

        $suggestions = [];
        foreach ($transactions as $tx) {
            $suggestions[$tx->id] = $reconciler->suggestions($tx);
        }

        return $this->view('bank.index', [
            'balance' => $this->biz()->balanceSnapshot(),
            'imports' => StatementImport::with('importer')->latest()->limit(12)->get(),
            'transactions' => $transactions,
            'suggestions' => $suggestions,
            'projects' => Project::orderBy('name')->get(),
            'openPayments' => PaymentTask::whereIn('status', ['prepared', 'sent'])->orderBy('payee_name')->get(),
            'filter' => $filter,
        ]);
    }

    public function import(Request $request, StatementImporter $importer)
    {
        $this->allow('finance');

        $request->validate([
            'statement' => ['required', 'file', 'max:'.config('business-core.upload_max_kb'), 'mimes:csv,txt'],
        ]);

        $file = $request->file('statement');

        try {
            $import = $importer->import($this->biz(), $file->getRealPath(), $file->getClientOriginalName(), $request->user());
        } catch (ImportException $e) {
            return back()->withErrors(array_merge(['statement' => $e->getMessage()], $e->problems));
        }

        $message = "Imported {$import->imported_count} of {$import->row_count} lines"
            .($import->duplicate_count ? " ({$import->duplicate_count} already on file were skipped)" : '').'.';
        if ($import->balance_warnings) {
            $message .= " Warning: {$import->balance_warnings} running balance(s) do not add up — check the file is complete.";
        }

        return back()->with('status', $message);
    }

    /** The original file exactly as uploaded. */
    public function download(StatementImport $import)
    {
        $this->allow('reports');
        $this->audit('statement.downloaded', $import);

        return Storage::disk(config('business-core.disk'))->download($import->path, $import->original_name);
    }

    /** Categorise, assign to a project, or flag as unresolved. The bank's own data is never changed. */
    public function update(Request $request, BankTransaction $transaction)
    {
        $this->allow('finance');

        $data = $request->validate([
            'category' => ['nullable', 'string', 'max:60'],
            'project_id' => ['nullable', $this->owned('business_projects')],
            'note' => ['nullable', 'string', 'max:500'],
            'flagged' => ['nullable', 'boolean'],
        ]);

        $transaction->fill([
            'category' => $data['category'] ?? null,
            'project_id' => $data['project_id'] ?? null,
            'note' => $data['note'] ?? null,
            'reviewed_by' => $request->user()->getKey(),
            'reviewed_at' => now(),
        ]);
        $transaction->refreshStatus((bool) ($data['flagged'] ?? false));
        $changes = $transaction->getDirty();
        $transaction->save();

        unset($changes['reviewed_at'], $changes['reviewed_by']);
        $this->audit('transaction.annotated', $transaction, $changes);

        return back()->with('status', 'Transaction updated.');
    }

    public function match(Request $request, BankTransaction $transaction, Reconciler $reconciler)
    {
        $this->allow('finance');

        $data = $request->validate(['payment_task_id' => ['required', $this->owned('business_payment_tasks')]]);

        try {
            $reconciler->match($transaction, PaymentTask::findOrFail($data['payment_task_id']));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['payment_task_id' => $e->getMessage()]);
        }

        return back()->with('status', 'Payment confirmed against the bank statement.');
    }

    public function unmatch(BankTransaction $transaction, Reconciler $reconciler)
    {
        $this->allow('finance');
        $reconciler->unmatch($transaction);

        return back()->with('status', 'Match removed.');
    }
}
