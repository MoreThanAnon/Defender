@extends(config('business-core.layout'))
@section('title', 'Storage & admin')

@section('content')
<h1 class="h3">Storage &amp; admin</h1>
<p class="text-muted">Every business on the platform. Keep businesses in storage until one is ready, then open it and make it live from its Settings screen.
    You see each business read-only unless you are also one of its members.</p>

<div class="row g-4">
    <div class="col-lg-8">
        @foreach (['stored' => 'In storage', 'live' => 'Live', 'archived' => 'Archived'] as $status => $heading)
            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">{{ $heading }} <span class="text-muted">({{ ($groups[$status] ?? collect())->count() }})</span></div>
                <div class="table-responsive"><table class="table mb-0 align-middle">
                    <thead><tr><th>Business</th><th>Type</th><th>Directors</th><th>People</th><th style="width: 16rem">Hand to a director</th></tr></thead>
                    <tbody>
                    @forelse ($groups[$status] ?? [] as $item)
                        <tr>
                            <td>
                                <a href="{{ route('bc.show', $item) }}">{{ $item->name }}</a>
                                <div class="small text-muted">{{ $item->legal_name }} {{ $item->registration_number }}</div>
                            </td>
                            <td>{{ $item->typeLabel() }}</td>
                            <td class="small">
                                @forelse ($item->directors as $director)
                                    <div>{{ \BusinessCore\Support\CurrentBusiness::displayName($director->user) }}</div>
                                @empty
                                    <span class="text-danger">none</span>
                                @endforelse
                            </td>
                            <td>{{ $item->memberships_count }}</td>
                            <td>
                                <form method="POST" action="{{ route('bc.admin.director', $item) }}" class="input-group input-group-sm">
                                    @csrf
                                    <input class="form-control" type="email" name="email" placeholder="their account email" required aria-label="Director's account email">
                                    <button class="btn btn-outline-primary">Assign</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">None.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
        @endforeach
    </div>

    <div class="col-lg-4">
        <form method="POST" action="{{ route('bc.admin.store') }}" class="card"><div class="card-body">
            @csrf
            <h2 class="h5">Put a business in storage</h2>
            <div class="mb-2"><label class="form-label" for="name">Name</label>
                <input class="form-control" id="name" name="name" required maxlength="120"></div>
            <div class="mb-2"><label class="form-label" for="type">Type</label>
                <select class="form-select" id="type" name="type">
                    @foreach (config('business-core.types') as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select></div>
            <div class="mb-2"><label class="form-label" for="notes">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea></div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="join" name="join_as_director" checked>
                <label class="form-check-label" for="join">Make me its director for now (so I can set it up)</label>
            </div>
            <button class="btn btn-primary">Add to storage</button>
        </div></form>
    </div>
</div>
@endsection
