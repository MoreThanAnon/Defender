@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="mb-1">My Assets</h1>
            <p class="text-muted mb-0">Your funded assets, governance links, voting eligibility and operating status.</p>
        </div>
        <a href="{{ route('properties.index') }}" class="btn btn-outline-primary">Browse Assets</a>
    </div>

    <div class="alert alert-warning">
        <strong>Important:</strong>
        this page is an investor information dashboard. It does not provide legal, tax, regulatory,
        financial, or investment advice, and it does not certify that any asset structure is compliant.
    </div>

    @if($offerings->isEmpty())
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-2">No funded assets yet</h5>
                <p class="text-muted mb-3">When an offering settles and your equity or loan position is created, it will appear here.</p>
                <a href="{{ route('properties.index') }}" class="btn btn-primary">Browse available assets</a>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($offerings as $offering)
                @php
                    $equity = $offering->equityPositions;
                    $loans = $offering->loanNotes;
                    $roles = $syncs->get($offering->id, collect());
                    $governance = $offering->decidimGovernanceSpace;
                    $operations = $offering->assetOperationsChecklist;
                    $canVote = $roles->contains(fn ($sync) => $sync->can_vote);
                    $votingBp = $roles->sum('voting_bp');
                @endphp
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <h4 class="mb-1">
                                        @if($offering->property)
                                            <a href="{{ route('property.show', $offering->property->id) }}">{{ $offering->property->name }}</a>
                                        @else
                                            Asset #{{ $offering->property_id }}
                                        @endif
                                    </h4>
                                    <div class="text-muted">Offering #{{ $offering->id }} — {{ $offering->name }}</div>
                                </div>
                                <span class="badge bg-success">{{ $offering->status }}</span>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted small">Equity ownership</div>
                                        <div class="fs-5 fw-bold">{{ number_format($equity->sum('economic_bp') / 100, 4) }}%</div>
                                        <div class="small text-muted">{{ $equity->count() }} position(s)</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted small">Loan principal</div>
                                        <div class="fs-5 fw-bold">EUR {{ number_format($loans->sum('principal_cents') / 100, 2) }}</div>
                                        <div class="small text-muted">{{ $loans->count() }} loan note(s)</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted small">Voting eligibility</div>
                                        <div class="fs-5 fw-bold">{{ $canVote ? 'Eligible' : 'Not ordinary voter' }}</div>
                                        <div class="small text-muted">{{ number_format($votingBp / 100, 4) }}% voting basis</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted small">Operations review</div>
                                        <div class="fs-5 fw-bold">{{ $operations?->reviewed_at ? 'Reviewed' : 'Pending' }}</div>
                                        <div class="small text-muted">{{ $operations?->reviewed_at?->diffForHumans() ?? 'No operating checklist yet' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <h6>Governance</h6>
                                    @if($governance?->decidim_assembly_url)
                                        <a href="{{ $governance->decidim_assembly_url }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                                            Open Decidim Assembly
                                        </a>
                                    @else
                                        <p class="text-muted mb-0">Governance space has not been created yet.</p>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <h6>Roles</h6>
                                    @if($roles->isEmpty())
                                        <p class="text-muted mb-0">No Decidim role sync recorded yet.</p>
                                    @else
                                        <ul class="mb-0 small">
                                            @foreach($roles as $role)
                                                <li>
                                                    {{ str_replace('_', ' ', ucfirst($role->role)) }}
                                                    @if($role->can_vote)
                                                        <span class="text-success">(voting)</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <h6>Operating status</h6>
                                    <ul class="mb-0 small">
                                        <li>Bank balance: {{ $operations?->bank_balance ? 'EUR '.number_format($operations->bank_balance, 2) : 'Not published' }}</li>
                                        <li>Bills/costs due: {{ count($operations?->bills_due ?? []) }}</li>
                                        <li>Tasks/due diligence: {{ count($operations?->maintenance_tasks ?? []) }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @include('components.footer')
</div>
@endsection
