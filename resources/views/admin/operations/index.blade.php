@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="mb-1">Asset Operations</h1>
            <p class="text-muted mb-0">MVP operating checklist for any settled asset: rental house, shop, land, hotel, or other buyable business/property.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Back to Admin</a>
    </div>

    <div class="alert alert-warning">
        <strong>Legal/compliance reminder:</strong>
        this page tracks review tasks only. It does not certify legal, regulatory, tax, investor-rights,
        Central Bank, AML/KYC, data-protection, or property-structure compliance.
    </div>

    <div class="row mb-4">
        @foreach([
            'Settled offerings' => $summary['settled'],
            'Operations checklists' => $summary['checklists'],
            'Reviewed' => $summary['reviewed'],
            'With health alerts' => $summary['with_health_alerts'],
            'Legal review pending' => $summary['legal_pending'],
        ] as $label => $value)
            <div class="col-md-3 mb-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fs-3 fw-bold">{{ $value }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Settled asset operations</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Offering</th>
                        <th>Property</th>
                        <th>Decidim</th>
                        <th>Checklist</th>
                        <th>Health alerts</th>
                        <th>Last reviewed</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($offerings as $offering)
                        <tr>
                            <td>#{{ $offering->id }} — {{ $offering->name }}</td>
                            <td>{{ $offering->property?->name ?? 'Unknown' }}</td>
                            <td>
                                @if($offering->decidimGovernanceSpace?->decidim_assembly_url)
                                    <a href="{{ $offering->decidimGovernanceSpace->decidim_assembly_url }}" target="_blank" rel="noopener">
                                        Open Assembly
                                    </a>
                                @else
                                    <span class="text-muted">Not created</span>
                                @endif
                            </td>
                            <td>
                                @if($offering->assetOperationsChecklist)
                                    <span class="badge bg-success">Started</span>
                                @else
                                    <span class="badge bg-warning">Not started</span>
                                @endif
                            </td>
                            <td style="min-width: 220px;">
                                @forelse($healthAlerts[$offering->id] ?? [] as $alert)
                                    <span class="badge bg-{{ $alert['level'] }} me-1 mb-1">{{ $alert['label'] }}</span>
                                @empty
                                    <span class="badge bg-success">No current alerts</span>
                                @endforelse
                            </td>
                            <td>{{ $offering->assetOperationsChecklist?->reviewed_at?->diffForHumans() ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.operations.show', $offering) }}" class="btn btn-primary btn-sm">
                                    Open checklist
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted p-4">No settled offerings yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($offerings->hasPages())
            <div class="card-footer bg-white">
                {{ $offerings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
