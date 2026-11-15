@extends(config('business-core.layout'))
@section('title', 'My businesses')

@section('content')
<div class="d-flex align-items-center mb-3">
    <h1 class="h3 mb-0">My businesses</h1>
    <a href="{{ route('bc.create') }}" class="btn btn-primary ms-auto">Add a business</a>
</div>

@if ($memberships->isEmpty())
    <div class="card"><div class="card-body">
        <p class="mb-2">You are not part of any business yet.</p>
        <p class="text-muted mb-0">Add one for each house, business or piece of land you run, or open the invitation link a director sent you.</p>
    </div></div>
@else
    <div class="row g-3">
        @foreach ($memberships as $membership)
            <div class="col-md-6 col-lg-4">
                <a href="{{ route('bc.show', $membership->business) }}" class="card h-100 text-decoration-none text-reset">
                    <div class="card-body">
                        <div class="d-flex gap-2 mb-2">
                            <span class="badge text-bg-light border">{{ $membership->business->typeLabel() }}</span>
                            <span class="badge {{ $membership->business->isLive() ? 'text-bg-success' : 'text-bg-warning' }}">{{ ucfirst($membership->business->status) }}</span>
                        </div>
                        <h2 class="h5">{{ $membership->business->name }}</h2>
                        <div class="small text-muted">{{ $membership->roleLabel() }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endif
@endsection
