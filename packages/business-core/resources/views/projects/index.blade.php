@extends(config('business-core.layout'))
@section('title', 'Budgets & projects')

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <h1 class="h4">Budgets &amp; projects</h1>
        <div class="card mb-4"><div class="table-responsive"><table class="table mb-0 align-middle">
            <thead><tr><th>Project</th><th>Asset</th><th>Status</th><th>Due</th>
                @if ($showMoney)<th class="num">Budget</th><th class="num">Paid</th><th class="num">Committed</th><th class="num">Left</th>@endif</tr></thead>
            <tbody>
            @forelse ($projects as $project)
                @php($paid = $showMoney ? $project->paid() : 0)
                @php($committed = $showMoney ? $project->committed() : 0)
                <tr>
                    <td><a href="{{ route('bc.projects.show', $project) }}">{{ $project->name }}</a></td>
                    <td>{{ $project->asset?->name ?? '—' }}</td>
                    <td>{{ \BusinessCore\Models\Project::STATUSES[$project->status] ?? $project->status }}</td>
                    <td class="text-nowrap">{{ $project->due_date?->format('j M Y') ?? '—' }}</td>
                    @if ($showMoney)
                        <td class="num">@money($project->budget_amount)</td>
                        <td class="num">@money($paid)</td>
                        <td class="num">@money($committed)</td>
                        <td class="num {{ $project->budget_amount !== null && $project->budget_amount - $paid - $committed < 0 ? 'text-danger fw-bold' : '' }}">
                            @if ($project->budget_amount !== null)@money($project->budget_amount - $paid - $committed)@else — @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="8" class="text-muted">No projects yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if ($showMoney)
            <div class="card-footer bg-white small text-muted">Paid = money out on imported bank statements assigned to the project. Committed = payment tasks prepared or paid but not yet seen on a statement.</div>
        @endif
        </div>

        <h2 class="h5">Asset register</h2>
        <div class="card"><table class="table mb-0">
            <thead><tr><th>Asset</th><th>Kind</th><th>Location</th><th>Projects</th></tr></thead>
            <tbody>
            @forelse ($assets as $asset)
                <tr><td>{{ $asset->name }}<div class="small text-muted">{{ $asset->notes }}</div></td><td>{{ $asset->kindLabel() }}</td><td>{{ $asset->location ?: '—' }}</td><td>{{ $asset->projects_count }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-muted">Nothing registered. Add your land, buildings and equipment.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    @bizcan('operate')
    <div class="col-lg-4">
        <form method="POST" action="{{ route('bc.projects.store') }}" class="card mb-3"><div class="card-body">
            @csrf
            <h2 class="h6">New project / cost centre</h2>
            @include('business-core::projects._fields', ['project' => null])
            <button class="btn btn-primary btn-sm mt-2">Create project</button>
        </div></form>

        <form method="POST" action="{{ route('bc.assets.store') }}" class="card"><div class="card-body">
            @csrf
            <h2 class="h6">Add an asset</h2>
            <input class="form-control form-control-sm mb-2" name="name" placeholder="Name, e.g. North field" required aria-label="Asset name">
            <select class="form-select form-select-sm mb-2" name="kind" aria-label="Asset kind">
                @foreach (config('business-core.asset_kinds') as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
            </select>
            <input class="form-control form-control-sm mb-2" name="location" placeholder="Location / address" aria-label="Location">
            <input class="form-control form-control-sm mb-2" name="notes" placeholder="Notes" aria-label="Notes">
            <button class="btn btn-outline-primary btn-sm">Add asset</button>
        </div></form>
    </div>
    @endbizcan
</div>
@endsection
