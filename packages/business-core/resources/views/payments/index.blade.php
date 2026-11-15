@extends(config('business-core.layout'))
@section('title', 'Payments')

@section('content')
<h1 class="h4">Payment tasks</h1>
<p class="text-muted">A checklist of payments to make. You pay each one yourself in your own bank; nothing is sent from here.
    A task is only marked confirmed when the payment shows up on an imported bank statement.</p>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card"><div class="table-responsive"><table class="table mb-0 align-middle">
            <thead><tr><th>Pay to</th><th>Reference</th><th class="num">Amount</th><th>Due</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td>{{ $payment->payee_name }}
                        <div class="small text-muted">
                            @if ($payment->project){{ $payment->project->name }}@endif
                            @if ($payment->proposal) · approved: <a href="{{ route('bc.proposals.show', $payment->proposal) }}">{{ \Illuminate\Support\Str::limit($payment->proposal->title, 40) }}</a>@endif
                        </div>
                        @if ($payment->isOpen() && $payment->supplier?->payment_details)
                            @bizcan('finance')
                                <details class="small"><summary>Payee details</summary><div style="white-space: pre-line">{{ $payment->supplier->payment_details }}</div>
                                    <div class="text-muted">Check these against the invoice before paying.</div></details>
                            @endbizcan
                        @endif
                    </td>
                    <td>{{ $payment->reference ?: '—' }}</td>
                    <td class="num">@money($payment->amount)</td>
                    <td class="text-nowrap {{ $payment->isOpen() && $payment->due_date?->isPast() ? 'text-danger' : '' }}">{{ $payment->due_date?->format('j M Y') ?? '—' }}</td>
                    <td><span class="badge {{ ['prepared' => 'text-bg-warning', 'sent' => 'text-bg-info', 'reconciled' => 'text-bg-success', 'cancelled' => 'text-bg-secondary'][$payment->status] }}">{{ $payment->statusLabel() }}</span>
                        @if ($payment->transaction)<div class="small text-muted">Statement line {{ $payment->transaction->booked_on->format('j M Y') }}</div>@endif</td>
                    <td class="text-nowrap">
                        @bizcan('finance')
                            @if ($payment->status === 'prepared')
                                @if ($business->bank_url)<a class="btn btn-sm btn-outline-primary" href="{{ $business->bank_url }}" target="_blank" rel="noopener noreferrer">Open my bank ↗</a>@endif
                                <form method="POST" action="{{ route('bc.payments.sent', $payment) }}" class="d-inline">@csrf<button class="btn btn-sm btn-primary">I've paid this</button></form>
                            @endif
                            @if ($payment->isOpen())
                                <form method="POST" action="{{ route('bc.payments.cancel', $payment) }}" class="d-inline">@csrf<button class="btn btn-sm btn-link text-secondary">Cancel</button></form>
                            @endif
                        @endbizcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">No payment tasks.</td></tr>
            @endforelse
            </tbody>
        </table></div></div>
    </div>

    @bizcan('finance')
    <div class="col-lg-4">
        <form method="POST" action="{{ route('bc.payments.store') }}" class="card"><div class="card-body">
            @csrf
            <h2 class="h6">Prepare a payment</h2>
            <div class="mb-2"><label class="form-label small mb-0" for="proposal_id">Approved proposal</label>
                <select class="form-select form-select-sm" id="proposal_id" name="proposal_id"><option value="">None</option>
                    @foreach ($approvedProposals as $proposal)<option value="{{ $proposal->id }}" @selected((int) old('proposal_id', $fromProposal?->id) === $proposal->id)>{{ $proposal->title }} ({{ $proposal->amount }})</option>@endforeach
                </select></div>
            <div class="mb-2"><label class="form-label small mb-0" for="supplier_id">Supplier</label>
                <select class="form-select form-select-sm" id="supplier_id" name="supplier_id"><option value="">None</option>
                    @foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((int) old('supplier_id', $fromProposal?->supplier_id) === $supplier->id)>{{ $supplier->name }}</option>@endforeach
                </select></div>
            <div class="mb-2"><label class="form-label small mb-0" for="payee_name">Pay to (name on the account)</label>
                <input class="form-control form-control-sm" id="payee_name" name="payee_name" value="{{ old('payee_name', $fromProposal?->supplier?->name) }}" required></div>
            <div class="row g-2">
                <div class="col-6"><label class="form-label small mb-0" for="amount">Amount</label>
                    <input class="form-control form-control-sm" id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount', $fromProposal?->amount) }}" required></div>
                <div class="col-6"><label class="form-label small mb-0" for="due_date">Due</label>
                    <input class="form-control form-control-sm" id="due_date" name="due_date" type="date" value="{{ old('due_date') }}"></div>
                <div class="col-6"><label class="form-label small mb-0" for="reference">Invoice / reference</label>
                    <input class="form-control form-control-sm" id="reference" name="reference" value="{{ old('reference') }}" maxlength="100"></div>
                <div class="col-6"><label class="form-label small mb-0" for="project_id">Project</label>
                    <select class="form-select form-select-sm" id="project_id" name="project_id"><option value="">None</option>
                        @foreach ($projects as $project)<option value="{{ $project->id }}" @selected((int) old('project_id', $fromProposal?->project_id) === $project->id)>{{ $project->name }}</option>@endforeach
                    </select></div>
            </div>
            <button class="btn btn-primary btn-sm mt-3">Prepare</button>
            <div class="form-text">Use the reference on the bank transfer so the statement line is easy to match.</div>
        </div></form>
    </div>
    @endbizcan
</div>
@endsection
