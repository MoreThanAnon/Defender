<?php

namespace BusinessCore\Http\Controllers;

use BusinessCore\Models\PaymentTask;
use BusinessCore\Models\Project;
use BusinessCore\Models\Proposal;
use BusinessCore\Models\Supplier;
use Illuminate\Http\Request;

/**
 * Payment instructions. This prepares and tracks; it never pays. The transfer is
 * made by the business in its own bank, and only a bank statement line settles a task.
 */
class PaymentTaskController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($this->current()->can('reports') || $this->current()->can('finance'), 403);

        return $this->view('payments.index', [
            'payments' => PaymentTask::with(['supplier', 'project', 'proposal', 'transaction'])
                ->orderByRaw("case status when 'prepared' then 0 when 'sent' then 1 when 'reconciled' then 2 else 3 end")->latest()->get(),
            'projects' => Project::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'approvedProposals' => Proposal::where('status', 'approved')->latest('decided_at')->get(),
            'fromProposal' => $request->filled('proposal') ? Proposal::where('status', 'approved')->find($request->query('proposal')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $this->allow('finance');

        $data = $request->validate([
            'payee_name' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'reference' => ['nullable', 'string', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'supplier_id' => ['nullable', $this->owned('business_suppliers')],
            'project_id' => ['nullable', $this->owned('business_projects')],
            'proposal_id' => ['nullable', $this->owned('business_proposals')],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! empty($data['proposal_id'])) {
            $proposal = Proposal::findOrFail($data['proposal_id']);
            if ($proposal->status !== 'approved') {
                return back()->withInput()->withErrors(['proposal_id' => 'That proposal has not been approved.']);
            }
            if ($proposal->amount !== null && (float) $data['amount'] > (float) $proposal->amount) {
                return back()->withInput()->withErrors(['amount' => "This is more than the approved amount ({$proposal->amount})."]);
            }
            $data['project_id'] ??= $proposal->project_id;
        }

        $payment = PaymentTask::create($data + ['created_by' => $request->user()->getKey()]);
        $this->audit('payment.prepared', $payment, ['payee' => $payment->payee_name, 'amount' => $payment->amount]);

        return redirect()->route('bc.payments.index')->with('status', 'Payment task prepared. Pay it in your own bank, then mark it as paid.');
    }

    /** The person says they made the transfer in the bank. Still unconfirmed until a statement shows it. */
    public function markSent(Request $request, PaymentTask $payment)
    {
        $this->allow('finance');
        abort_unless($payment->status === 'prepared', 422);

        $payment->update(['status' => 'sent', 'sent_by' => $request->user()->getKey(), 'sent_at' => now()]);
        $this->audit('payment.marked_sent', $payment, ['amount' => $payment->amount]);

        return back()->with('status', 'Marked as paid in the bank. It will be confirmed when the payment appears on an imported statement.');
    }

    public function cancel(PaymentTask $payment)
    {
        $this->allow('finance');
        abort_unless($payment->isOpen(), 422);

        $payment->update(['status' => 'cancelled']);
        $this->audit('payment.cancelled', $payment);

        return back()->with('status', 'Payment task cancelled.');
    }
}
