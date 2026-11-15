@extends(config('business-core.layout'))
@section('title', 'Proposals')

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <h1 class="h4">Proposals &amp; decisions</h1>
        <div class="card"><div class="table-responsive"><table class="table mb-0 align-middle">
            <thead><tr><th>Proposal</th><th>Project</th><th class="num">Amount</th><th>Votes</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($proposals as $proposal)
                <tr>
                    <td><a href="{{ route('bc.proposals.show', $proposal) }}">{{ $proposal->title }}</a>
                        <div class="small text-muted">by {{ \BusinessCore\Support\CurrentBusiness::displayName($proposal->author) }}, {{ $proposal->created_at->format('j M Y') }}
                            @if ($proposal->isOpen() && $proposal->closes_at) · closes {{ $proposal->closes_at->format('j M, H:i') }}@endif</div></td>
                    <td>{{ $proposal->project?->name ?? '—' }}</td>
                    <td class="num">@money($proposal->amount)</td>
                    <td>{{ $proposal->votes_count }}</td>
                    <td><span class="badge {{ ['open' => 'text-bg-warning', 'approved' => 'text-bg-success', 'rejected' => 'text-bg-secondary'][$proposal->status] ?? 'text-bg-light' }}">{{ ucfirst($proposal->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No proposals yet.</td></tr>
            @endforelse
            </tbody>
        </table></div></div>
    </div>

    @bizcan('propose')
    <div class="col-lg-4">
        <form method="POST" action="{{ route('bc.proposals.store') }}" class="card"><div class="card-body">
            @csrf
            <h2 class="h6">New proposal</h2>
            <div class="mb-2"><label class="form-label small mb-0" for="title">What do you propose?</label>
                <input class="form-control form-control-sm" id="title" name="title" value="{{ old('title') }}" required placeholder="e.g. Replace the broken water pump"></div>
            <div class="mb-2"><label class="form-label small mb-0" for="body">Details</label>
                <textarea class="form-control form-control-sm" id="body" name="body" rows="3">{{ old('body') }}</textarea></div>
            <div class="row g-2">
                <div class="col-6"><label class="form-label small mb-0" for="amount">Cost</label>
                    <input class="form-control form-control-sm" id="amount" name="amount" type="number" step="0.01" min="0" value="{{ old('amount') }}"></div>
                <div class="col-6"><label class="form-label small mb-0" for="closes_at">Voting closes</label>
                    <input class="form-control form-control-sm" id="closes_at" name="closes_at" type="datetime-local" value="{{ old('closes_at') }}"></div>
                <div class="col-6"><label class="form-label small mb-0" for="project_id">Project</label>
                    <select class="form-select form-select-sm" id="project_id" name="project_id"><option value="">None</option>
                        @foreach ($projects as $project)<option value="{{ $project->id }}" @selected((int) old('project_id') === $project->id)>{{ $project->name }}</option>@endforeach
                    </select></div>
                <div class="col-6"><label class="form-label small mb-0" for="supplier_id">Supplier</label>
                    <select class="form-select form-select-sm" id="supplier_id" name="supplier_id"><option value="">None</option>
                        @foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((int) old('supplier_id') === $supplier->id)>{{ $supplier->name }}</option>@endforeach
                    </select></div>
            </div>
            <button class="btn btn-primary btn-sm mt-3">Open for discussion</button>
            <div class="form-text">Attach the supplier quote on the next screen.</div>
        </div></form>
    </div>
    @endbizcan
</div>
@endsection
