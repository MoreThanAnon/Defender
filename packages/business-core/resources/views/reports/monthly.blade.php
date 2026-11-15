@extends(config('business-core.layout'))
@section('title', 'Report '.$period)

@section('content')
<div class="d-flex align-items-center gap-2 mb-3 no-print">
    <a href="{{ route('bc.reports.index') }}" class="small">← Reports</a>
    <span class="ms-auto"></span>
    <a class="btn btn-sm btn-outline-primary" href="{{ route('bc.reports.monthly.csv', ['period' => $period]) }}">Download CSV</a>
    <button class="btn btn-sm btn-outline-primary" onclick="window.print()">Print / save as PDF</button>
</div>

<h1 class="h3 mb-0">{{ $business->name }}</h1>
<p class="text-muted">Management report, {{ $start->format('F Y') }}@if ($business->legal_name) · {{ $business->legal_name }} {{ $business->registration_number }}@endif</p>

@if ($business->isStored())
    <div class="alert alert-warning">This business is in storage. The figures below are practice data, not a real company's accounts.</div>
@endif
@if ($signoff)
    <div class="alert alert-success">Signed off by {{ \BusinessCore\Support\CurrentBusiness::displayName($signoff->signer) }} on {{ $signoff->signed_at->format('j M Y H:i') }}.@if ($signoff->note) “{{ $signoff->note }}”@endif</div>
@else
    <div class="alert alert-light border">Draft — not yet signed off by a director.</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card"><div class="card-body"><div class="bc-source">Opening cash</div><div class="bc-figure">@money($opening)</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card"><div class="card-body"><div class="bc-source">Receipts</div><div class="bc-figure">@money($receipts)</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card"><div class="card-body"><div class="bc-source">Payments</div><div class="bc-figure">@money($payments)</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card"><div class="card-body"><div class="bc-source">Closing cash</div><div class="bc-figure">@money($closing)</div></div></div></div>
</div>

@if ($unreviewed)
    <div class="alert alert-warning">{{ $unreviewed }} bank line(s) this month are unreviewed or flagged. Category totals below are incomplete until they are resolved.</div>
@endif

<div class="row g-4">
    <div class="col-lg-6">
        <h2 class="h5">By category</h2>
        <table class="table table-sm bg-white border">
            <thead><tr><th>Category</th><th class="num">Lines</th><th class="num">In</th><th class="num">Out</th></tr></thead>
            @forelse ($categories as $name => $row)
                <tr><td>{{ $name }}</td><td class="num">{{ $row['count'] }}</td><td class="num">@money($row['in'])</td><td class="num">@money($row['out'])</td></tr>
            @empty
                <tr><td colspan="4" class="text-muted">No bank lines imported for this month.</td></tr>
            @endforelse
        </table>
    </div>
    <div class="col-lg-6">
        <h2 class="h5">Budget against actual</h2>
        <table class="table table-sm bg-white border">
            <thead><tr><th>Project</th><th class="num">Budget</th><th class="num">Paid this month</th><th class="num">Paid to date</th><th class="num">Committed</th></tr></thead>
            @forelse ($projects as $row)
                <tr><td>{{ $row['project']->name }}</td><td class="num">@money($row['budget'])</td><td class="num">@money($row['paid_in_month'])</td>
                    <td class="num {{ $row['budget'] !== null && $row['paid'] + $row['committed'] > $row['budget'] ? 'text-danger fw-bold' : '' }}">@money($row['paid'])</td><td class="num">@money($row['committed'])</td></tr>
            @empty
                <tr><td colspan="5" class="text-muted">No projects.</td></tr>
            @endforelse
        </table>
    </div>

    <div class="col-lg-6">
        <h2 class="h5">Decisions recorded this month</h2>
        <table class="table table-sm bg-white border">
            @forelse ($decisions as $proposal)
                <tr><td>{{ $proposal->title }}</td><td class="num">@money($proposal->amount)</td><td>{{ ucfirst($proposal->status) }} by {{ \BusinessCore\Support\CurrentBusiness::displayName($proposal->decider) }}, {{ $proposal->decided_at->format('j M') }}</td></tr>
            @empty
                <tr><td class="text-muted">None.</td></tr>
            @endforelse
        </table>
    </div>
    <div class="col-lg-6">
        <h2 class="h5">Outstanding now</h2>
        <table class="table table-sm bg-white border">
            @foreach ($pending_payments as $payment)
                <tr><td>Payment: {{ $payment->payee_name }} {{ $payment->reference }}</td><td class="num">@money($payment->amount)</td><td>{{ $payment->statusLabel() }}</td></tr>
            @endforeach
            @foreach ($overdue_tasks as $task)
                <tr><td>Task overdue: {{ $task->title }}</td><td></td><td class="text-danger">due {{ $task->due_date->format('j M Y') }}</td></tr>
            @endforeach
            @if ($pending_payments->isEmpty() && $overdue_tasks->isEmpty())
                <tr><td class="text-muted">Nothing outstanding.</td></tr>
            @endif
        </table>
    </div>
</div>

<h2 class="h5 mt-3">Bank lines</h2>
<table class="table table-sm bg-white border small">
    <thead><tr><th>Date</th><th>Description</th><th>Category</th><th>Project</th><th class="num">Amount</th><th class="num">Balance</th></tr></thead>
    @foreach ($transactions as $tx)
        <tr><td class="text-nowrap">{{ $tx->booked_on->format('j M') }}</td><td>{{ $tx->description }}</td><td>{{ $tx->category ?: '—' }}</td><td>{{ $tx->project?->name }}</td>
            <td class="num">@money($tx->amount)</td><td class="num">@money($tx->balance_after)</td></tr>
    @endforeach
</table>

<div class="small text-muted border-top pt-3 mt-4">
    <p class="mb-1"><strong>Where these figures come from.</strong> Cash figures are taken only from bank statement files uploaded by the business:
        @forelse ($imports as $import){{ $import->original_name }} (SHA-256 {{ substr($import->sha256, 0, 12) }}…, imported {{ $import->created_at->format('j M Y') }})@if (! $loop->last); @endif @empty none for this period @endforelse.
        Opening and closing cash are the balances printed on those statements; a dash means the statement gave no balance.</p>
    <p class="mb-1">Budgets are planned figures entered by the business. "Committed" payments have been prepared or reported as paid but are not yet confirmed by a statement.
        Categories are the business's own annotations and are not verified.</p>
    <p class="mb-0">This is a management summary, not statutory accounts, and has not been audited. Generated {{ $generated_at->format('j M Y H:i') }}.</p>
</div>

@if (! $signoff)
    @bizcan('decide')
        <form method="POST" action="{{ route('bc.reports.signoff', ['period' => $period]) }}" class="card mt-4 no-print"><div class="card-body d-flex gap-2 align-items-end">
            @csrf
            <div class="flex-grow-1"><label class="form-label small mb-0" for="note">Sign-off note (optional)</label><input class="form-control form-control-sm" id="note" name="note" maxlength="500"></div>
            <button class="btn btn-primary btn-sm">Sign off this report</button>
        </div></form>
    @endbizcan
@endif
@endsection
