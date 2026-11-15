@extends(config('business-core.layout'))
@section('title', $project->name)

@section('content')
<a href="{{ route('bc.projects.index') }}" class="small">← All projects</a>
<h1 class="h4 mt-1">{{ $project->name }}</h1>
<p class="text-muted">{{ $project->description }}</p>

<div class="row g-4">
    <div class="col-lg-8">
        @if ($showMoney)
            @php($paid = $project->paid())
            @php($committed = $project->committed())
            <div class="row g-3 mb-3">
                <div class="col"><div class="card"><div class="card-body"><div class="bc-source">Budget (planned)</div><div class="bc-figure">@money($project->budget_amount)</div></div></div></div>
                <div class="col"><div class="card"><div class="card-body"><div class="bc-source">Paid (on bank statements)</div><div class="bc-figure">@money($paid)</div></div></div></div>
                <div class="col"><div class="card"><div class="card-body"><div class="bc-source">Committed (not yet on a statement)</div><div class="bc-figure">@money($committed)</div></div></div></div>
            </div>

            <div class="card mb-3"><div class="card-header bg-white fw-semibold">Bank lines assigned to this project</div>
                <table class="table table-sm mb-0">
                    @forelse ($transactions as $tx)
                        <tr><td class="text-nowrap">{{ $tx->booked_on->format('j M Y') }}</td><td>{{ $tx->description }}</td><td>{{ $tx->category }}</td><td class="num">@money($tx->amount)</td></tr>
                    @empty
                        <tr><td class="text-muted">None yet. Assign lines on the Bank screen.</td></tr>
                    @endforelse
                </table>
            </div>
        @endif

        <div class="card mb-3"><div class="card-header bg-white fw-semibold">Payment tasks</div>
            <table class="table table-sm mb-0">
                @forelse ($payments as $payment)
                    <tr><td>{{ $payment->payee_name }} <span class="text-muted">{{ $payment->reference }}</span></td><td>{{ $payment->statusLabel() }}</td><td class="num">@money($payment->amount)</td></tr>
                @empty
                    <tr><td class="text-muted">None.</td></tr>
                @endforelse
            </table>
        </div>

        <div class="card mb-3"><div class="card-header bg-white fw-semibold">Proposals</div>
            <ul class="list-group list-group-flush">
                @forelse ($proposals as $proposal)
                    <li class="list-group-item d-flex"><a href="{{ route('bc.proposals.show', $proposal) }}">{{ $proposal->title }}</a><span class="ms-auto">{{ ucfirst($proposal->status) }}</span></li>
                @empty
                    <li class="list-group-item text-muted">None.</li>
                @endforelse
            </ul>
        </div>

        <div class="card mb-3"><div class="card-header bg-white fw-semibold">Tasks</div>
            <ul class="list-group list-group-flush">
                @forelse ($tasks as $task)
                    <li class="list-group-item d-flex"><span>{{ $task->title }}</span>
                        <span class="ms-auto small {{ $task->isOverdue() ? 'text-danger' : 'text-muted' }}">{{ \BusinessCore\Models\Task::STATUSES[$task->status] }}@if ($task->due_date) · due {{ $task->due_date->format('j M') }}@endif</span></li>
                @empty
                    <li class="list-group-item text-muted">None. Add tasks on the Tasks screen.</li>
                @endforelse
            </ul>
        </div>

        @include('business-core::documents._attached', ['documents' => $documents, 'attachTo' => 'project', 'attachId' => $project->id])
    </div>

    @bizcan('operate')
    <div class="col-lg-4">
        <form method="POST" action="{{ route('bc.projects.update', $project) }}" class="card"><div class="card-body">
            @csrf @method('PUT')
            <h2 class="h6">Edit project</h2>
            @include('business-core::projects._fields')
            <button class="btn btn-primary btn-sm mt-2">Save</button>
        </div></form>
    </div>
    @endbizcan
</div>
@endsection
