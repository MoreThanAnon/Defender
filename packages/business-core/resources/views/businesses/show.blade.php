@extends(config('business-core.layout'))
@section('title', $business->name)

@section('content')
<div class="row g-3 mb-3">
    @if ($balance)
        <div class="col-lg-5"><div class="card h-100"><div class="card-body">
            @include('business-core::partials.balance')
        </div></div></div>
        <div class="col-6 col-lg-2"><div class="card h-100"><div class="card-body">
            <div class="bc-source">In, last 30 days</div>
            <div class="bc-figure">@money($moneyIn)</div>
        </div></div></div>
        <div class="col-6 col-lg-2"><div class="card h-100"><div class="card-body">
            <div class="bc-source">Out, last 30 days</div>
            <div class="bc-figure">@money($moneyOut)</div>
        </div></div></div>
    @endif
    <div class="col-lg"><div class="card h-100"><div class="card-body">
        <div class="bc-source mb-1">Needs attention</div>
        <ul class="list-unstyled mb-0 small">
            <li><a href="{{ route('bc.proposals.index') }}"><strong>{{ $openProposalCount }}</strong> proposal(s) awaiting decision</a></li>
            @bizcan('reports')
                <li><a href="{{ route('bc.payments.index') }}"><strong>{{ $toPay }}</strong> payment(s) to make in your bank</a></li>
                <li><a href="{{ route('bc.payments.index') }}"><strong>{{ $awaitingStatement }}</strong> payment(s) awaiting bank confirmation</a></li>
                <li><a href="{{ route('bc.bank.index', ['show' => 'todo']) }}"><strong>{{ $unreviewed }}</strong> bank line(s) to review</a></li>
            @endbizcan
            <li><a href="{{ route('bc.tasks.index') }}"><strong>{{ $overdueTasks->count() }}</strong> task(s) overdue</a></li>
        </ul>
    </div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card"><div class="card-header bg-white fw-semibold">Budgets &amp; projects</div>
            <div class="table-responsive"><table class="table mb-0 align-middle">
                <thead><tr><th>Project</th><th>Status</th>@if ($balance)<th class="num">Budget</th><th class="num">Paid (bank-confirmed)</th>@endif</tr></thead>
                <tbody>
                @forelse ($projects as $project)
                    <tr>
                        <td><a href="{{ route('bc.projects.show', $project) }}">{{ $project->name }}</a></td>
                        <td>{{ \BusinessCore\Models\Project::STATUSES[$project->status] ?? $project->status }}</td>
                        @if ($balance)
                            <td class="num">@money($project->budget_amount)</td>
                            <td class="num">@money($project->paid())</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No active projects. <a href="{{ route('bc.projects.index') }}">Add one</a>.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-3"><div class="card-header bg-white fw-semibold">Open proposals</div>
            <ul class="list-group list-group-flush">
                @forelse ($openProposals as $proposal)
                    <li class="list-group-item d-flex">
                        <a href="{{ route('bc.proposals.show', $proposal) }}">{{ $proposal->title }}</a>
                        <span class="ms-auto text-nowrap">@if ($proposal->amount !== null)@money($proposal->amount)@endif</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Nothing waiting.</li>
                @endforelse
            </ul>
        </div>
        @if ($overdueTasks->isNotEmpty())
            <div class="card"><div class="card-header bg-white fw-semibold">Overdue tasks</div>
                <ul class="list-group list-group-flush">
                    @foreach ($overdueTasks as $task)
                        <li class="list-group-item d-flex"><span>{{ $task->title }}</span><span class="ms-auto text-danger small">due {{ $task->due_date->format('j M') }}</span></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endsection
