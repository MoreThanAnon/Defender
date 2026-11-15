@extends(config('business-core.layout'))
@section('title', 'Bank')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-lg-5"><div class="card h-100"><div class="card-body">
        @include('business-core::partials.balance')
    </div></div></div>
    <div class="col-lg-7"><div class="card h-100"><div class="card-body">
        <h2 class="h6">Import a bank statement</h2>
        @bizcan('finance')
            <form method="POST" action="{{ route('bc.bank.import') }}" enctype="multipart/form-data" class="d-flex gap-2 mb-2">
                @csrf
                <input class="form-control" type="file" name="statement" accept=".csv,.txt" required aria-label="Statement CSV file">
                <button class="btn btn-primary text-nowrap">Import CSV</button>
            </form>
            <div class="small text-muted">Download a CSV from your online banking and upload it here. Needs a date, a description and an amount (or debit/credit) column; dates are read day-first.
                The same file cannot be imported twice and overlapping lines are skipped. Nothing here connects to your bank.</div>
        @else
            <div class="small text-muted">Only the finance lead or a director can import statements.</div>
        @endbizcan
        @if ($imports->isNotEmpty())
            <details class="mt-2 small">
                <summary>Imported files ({{ $imports->count() }})</summary>
                <table class="table table-sm mt-2 mb-0">
                    <thead><tr><th>File</th><th>Period</th><th class="num">Lines</th><th class="num">Closing balance</th><th>By</th></tr></thead>
                    @foreach ($imports as $import)
                        <tr>
                            <td><a href="{{ route('bc.bank.download', $import) }}">{{ $import->original_name }}</a>
                                @if ($import->balance_warnings)<span class="badge text-bg-danger" title="Running balances do not add up">{{ $import->balance_warnings }} balance gap(s)</span>@endif</td>
                            <td class="text-nowrap">{{ $import->period_start?->format('j M') }} – {{ $import->period_end?->format('j M Y') }}</td>
                            <td class="num">{{ $import->imported_count }}@if ($import->duplicate_count) <span class="text-muted">(+{{ $import->duplicate_count }} dup.)</span>@endif</td>
                            <td class="num">@money($import->closing_balance)</td>
                            <td>{{ \BusinessCore\Support\CurrentBusiness::displayName($import->importer) }}, {{ $import->created_at->format('j M') }}</td>
                        </tr>
                    @endforeach
                </table>
            </details>
        @endif
    </div></div></div>
</div>

<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
    <h2 class="h5 mb-0">Transactions</h2>
    <div class="btn-group btn-group-sm ms-2">
        <a class="btn btn-outline-primary {{ $filter === 'all' ? 'active' : '' }}" href="{{ route('bc.bank.index') }}">All</a>
        <a class="btn btn-outline-primary {{ $filter === 'todo' ? 'active' : '' }}" href="{{ route('bc.bank.index', ['show' => 'todo']) }}">To review</a>
    </div>
    <form class="ms-auto d-flex gap-2" method="GET">
        @if ($filter !== 'all')<input type="hidden" name="show" value="{{ $filter }}">@endif
        <input class="form-control form-control-sm" name="q" value="{{ request('q') }}" placeholder="Search description" aria-label="Search description">
        <button class="btn btn-sm btn-outline-secondary">Search</button>
    </form>
</div>

<div class="card"><div class="table-responsive"><table class="table mb-0 align-middle small">
    <thead><tr><th>Date</th><th>Bank description</th><th class="num">Amount</th><th class="num">Balance</th><th style="min-width: 26rem">Your notes</th></tr></thead>
    <tbody>
    @forelse ($transactions as $tx)
        <tr>
            <td class="text-nowrap">{{ $tx->booked_on->format('j M Y') }}</td>
            <td>{{ $tx->description }}
                <div><span class="badge {{ ['unreviewed' => 'text-bg-secondary', 'flagged' => 'text-bg-danger', 'matched' => 'text-bg-success', 'categorised' => 'text-bg-light border'][$tx->status] ?? 'text-bg-light' }}">{{ \BusinessCore\Models\BankTransaction::STATUSES[$tx->status] ?? $tx->status }}</span></div>
            </td>
            <td class="num {{ (float) $tx->amount < 0 ? '' : 'text-success' }}">@money($tx->amount)</td>
            <td class="num text-muted">@money($tx->balance_after)</td>
            <td>
                @bizcan('finance')
                    <form method="POST" action="{{ route('bc.bank.update', $tx) }}" class="row g-1">
                        @csrf @method('PUT')
                        <div class="col-4"><input class="form-control form-control-sm" name="category" list="bc-categories" value="{{ $tx->category }}" placeholder="Category" aria-label="Category"></div>
                        <div class="col-4"><select class="form-select form-select-sm" name="project_id" aria-label="Project">
                            <option value="">No project</option>
                            @foreach ($projects as $project)<option value="{{ $project->id }}" @selected($tx->project_id === $project->id)>{{ $project->name }}</option>@endforeach
                        </select></div>
                        <div class="col-4 d-flex gap-1">
                            <div class="form-check mb-0 pt-1"><input class="form-check-input" type="checkbox" name="flagged" value="1" id="flag{{ $tx->id }}" @checked($tx->status === 'flagged')><label class="form-check-label" for="flag{{ $tx->id }}">Flag</label></div>
                            <button class="btn btn-sm btn-outline-primary ms-auto">Save</button>
                        </div>
                        <div class="col-12"><input class="form-control form-control-sm" name="note" value="{{ $tx->note }}" placeholder="Note (why flagged, what it was for…)" aria-label="Note"></div>
                    </form>

                    @if ($tx->paymentTask)
                        <form method="POST" action="{{ route('bc.bank.unmatch', $tx) }}" class="mt-1">
                            @csrf @method('DELETE')
                            Confirms payment to <strong>{{ $tx->paymentTask->payee_name }}</strong> {{ $tx->paymentTask->reference }}
                            <button class="btn btn-link btn-sm p-0 ms-1">undo match</button>
                        </form>
                    @elseif ((float) $tx->amount < 0 && $openPayments->isNotEmpty())
                        <form method="POST" action="{{ route('bc.bank.match', $tx) }}" class="d-flex gap-1 mt-1">
                            @csrf
                            <select class="form-select form-select-sm" name="payment_task_id" aria-label="Payment task to match">
                                @forelse ($suggestions[$tx->id] as $task)
                                    <option value="{{ $task->id }}">Suggested: {{ $task->payee_name }} {{ $task->reference }} ({{ $task->amount }})</option>
                                @empty
                                    <option value="">Match to a payment task…</option>
                                @endforelse
                                @foreach ($openPayments->whereNotIn('id', $suggestions[$tx->id]->pluck('id')) as $task)
                                    <option value="{{ $task->id }}">{{ $task->payee_name }} {{ $task->reference }} ({{ $task->amount }})</option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm {{ $suggestions[$tx->id]->isNotEmpty() ? 'btn-primary' : 'btn-outline-secondary' }} text-nowrap">Match</button>
                        </form>
                    @endif
                @else
                    {{ $tx->category ?: '—' }}@if ($tx->project) · {{ $tx->project->name }}@endif
                    @if ($tx->paymentTask)<div>Confirms payment to {{ $tx->paymentTask->payee_name }}</div>@endif
                    @if ($tx->note)<div class="text-muted">{{ $tx->note }}</div>@endif
                @endbizcan
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted p-4">No transactions yet. Import a statement to begin.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $transactions->links('pagination::bootstrap-5') }}</div>

<datalist id="bc-categories">
    @foreach (config('business-core.categories') as $category)<option value="{{ $category }}">@endforeach
</datalist>
@endsection
