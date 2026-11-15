@extends(config('business-core.layout'))
@section('title', $proposal->title)

@section('content')
<a href="{{ route('bc.proposals.index') }}" class="small">← All proposals</a>
<div class="d-flex align-items-start gap-3 mt-1">
    <h1 class="h4">{{ $proposal->title }}</h1>
    <span class="badge fs-6 {{ ['open' => 'text-bg-warning', 'approved' => 'text-bg-success', 'rejected' => 'text-bg-secondary'][$proposal->status] ?? 'text-bg-light' }}">{{ ucfirst($proposal->status) }}</span>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-3"><div class="card-body">
            <dl class="row mb-2 small">
                <dt class="col-sm-3">Proposed by</dt><dd class="col-sm-9">{{ \BusinessCore\Support\CurrentBusiness::displayName($proposal->author) }}, {{ $proposal->created_at->format('j M Y H:i') }}</dd>
                <dt class="col-sm-3">Cost</dt><dd class="col-sm-9">@money($proposal->amount) <span class="text-muted">(proposed, not yet spent)</span></dd>
                <dt class="col-sm-3">Project</dt><dd class="col-sm-9">@if ($proposal->project)<a href="{{ route('bc.projects.show', $proposal->project) }}">{{ $proposal->project->name }}</a>@else — @endif</dd>
                <dt class="col-sm-3">Supplier</dt><dd class="col-sm-9">{{ $proposal->supplier?->name ?? '—' }}</dd>
                <dt class="col-sm-3">Voting closes</dt><dd class="col-sm-9">{{ $proposal->closes_at?->format('j M Y H:i') ?? 'No deadline' }}</dd>
            </dl>
            <div style="white-space: pre-line">{{ $proposal->body }}</div>
        </div></div>

        @include('business-core::documents._attached', ['documents' => $documents, 'attachTo' => 'proposal', 'attachId' => $proposal->id])

        <div class="card"><div class="card-header bg-white fw-semibold">Discussion</div>
            <ul class="list-group list-group-flush">
                @forelse ($proposal->comments as $comment)
                    <li class="list-group-item">
                        <div class="small text-muted">{{ \BusinessCore\Support\CurrentBusiness::displayName($comment->user) }} · {{ $comment->created_at->format('j M Y H:i') }}</div>
                        <div style="white-space: pre-line">{{ $comment->body }}</div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No comments yet.</li>
                @endforelse
            </ul>
            @if (app(\BusinessCore\Support\CurrentBusiness::class)->role() !== 'auditor')
                <div class="card-body border-top">
                    <form method="POST" action="{{ route('bc.proposals.comment', $proposal) }}">
                        @csrf
                        <textarea class="form-control mb-2" name="body" rows="2" required placeholder="Add a comment" aria-label="Comment"></textarea>
                        <button class="btn btn-sm btn-outline-primary">Comment</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6">Vote</h2>
            <div class="d-flex gap-3 mb-2">
                <div><div class="bc-figure">{{ $tally['yes'] }}</div><div class="bc-source">Yes</div></div>
                <div><div class="bc-figure">{{ $tally['no'] }}</div><div class="bc-source">No</div></div>
                <div><div class="bc-figure">{{ $tally['abstain'] }}</div><div class="bc-source">Abstain</div></div>
            </div>
            <div class="small mb-2">{{ $tally['cast'] }} of {{ $tally['eligible'] }} eligible voted. Quorum ({{ $business->quorum_percent }}% = {{ $tally['quorum_needed'] }})
                <strong class="{{ $tally['quorum_met'] ? 'text-success' : 'text-danger' }}">{{ $tally['quorum_met'] ? 'met' : 'not met' }}</strong>.</div>
            @if ($proposal->votes->isNotEmpty())
                <details class="small mb-2"><summary>Who voted</summary>
                    @foreach ($proposal->votes as $vote)<div>{{ \BusinessCore\Support\CurrentBusiness::displayName($vote->user) }}: {{ $vote->choice }}</div>@endforeach
                </details>
            @endif
            @bizcan('vote')
                @if ($proposal->votingOpen())
                    <form method="POST" action="{{ route('bc.proposals.vote', $proposal) }}" class="btn-group w-100">
                        @csrf
                        @foreach (['yes' => 'Yes', 'no' => 'No', 'abstain' => 'Abstain'] as $value => $label)
                            <button name="choice" value="{{ $value }}" class="btn btn-sm {{ $myVote?->choice === $value ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</button>
                        @endforeach
                    </form>
                @else
                    <div class="small text-muted">Voting is closed.</div>
                @endif
            @endbizcan
            <div class="small text-muted mt-2">One person, one vote. This is an internal vote to inform the decision. It is not a formal shareholder or board resolution.</div>
        </div></div>

        <div class="card"><div class="card-body">
            <h2 class="h6">Decision</h2>
            @if ($proposal->isOpen())
                @bizcan('decide')
                    <form method="POST" action="{{ route('bc.proposals.decide', $proposal) }}">
                        @csrf
                        <textarea class="form-control form-control-sm mb-2" name="decision_note" rows="2" placeholder="Note for the record (optional)" aria-label="Decision note"></textarea>
                        <div class="d-flex gap-2">
                            <button name="decision" value="approved" class="btn btn-sm btn-primary">Approve</button>
                            <button name="decision" value="rejected" class="btn btn-sm btn-outline-secondary">Reject</button>
                        </div>
                        <div class="small text-muted mt-2">You are recording the company's own decision as an authorised director. Approving does not move any money.</div>
                    </form>
                @else
                    <div class="small text-muted">Waiting for a director to record the decision.</div>
                @endbizcan
            @else
                <p class="mb-1"><strong>{{ ucfirst($proposal->status) }}</strong> by {{ \BusinessCore\Support\CurrentBusiness::displayName($proposal->decider) }}<br>
                    <span class="small text-muted">{{ $proposal->decided_at?->format('j M Y H:i') }}</span></p>
                @if ($proposal->decision_note)<p class="small">{{ $proposal->decision_note }}</p>@endif
                @if ($proposal->status === 'approved')
                    @foreach ($proposal->paymentTasks as $payment)
                        <div class="small">Payment task: @money($payment->amount) — {{ $payment->statusLabel() }}</div>
                    @endforeach
                    @bizcan('finance')
                        <a class="btn btn-sm btn-primary mt-2" href="{{ route('bc.payments.index', ['proposal' => $proposal->id]) }}">Prepare payment task</a>
                    @endbizcan
                @endif
            @endif
        </div></div>
    </div>
</div>
@endsection
