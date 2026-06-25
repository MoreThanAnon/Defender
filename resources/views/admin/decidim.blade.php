@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="mb-1">Decidim Integration Status</h1>
            <p class="text-muted mb-0">Track Assembly creation, investor access sync, failures and retries.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Back to Admin</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="alert alert-warning">
        <strong>Legal review required:</strong>
        these are technical sync checks only. They do not confirm Irish crowdfunding, company law,
        investor-rights, tax, AML/KYC, Central Bank, property ownership, or data-protection compliance.
    </div>

    <div class="row mb-4">
        @foreach([
            'Governance spaces' => $summary['spaces_total'],
            'Created' => $summary['created'],
            'Failed' => $summary['failed'],
            'Pending' => $summary['pending'],
            'Syncing' => $summary['syncing'],
            'Synced investor roles' => $summary['synced_roles'],
            'Settled missing Decidim' => $summary['settled_without_space'],
            'Failed queue jobs' => $summary['failed_jobs'],
        ] as $label => $value)
            <div class="col-md-4 col-xl-2 mb-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fs-3 fw-bold">{{ $value }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Governance spaces</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Offering</th>
                        <th>Property</th>
                        <th>Status</th>
                        <th>Assembly</th>
                        <th>Components</th>
                        <th>Investor roles</th>
                        <th>Retries</th>
                        <th>Last sync</th>
                        <th>Last error</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($spaces as $space)
                        @php
                            $badge = match($space->status) {
                                'created' => 'success',
                                'failed' => 'danger',
                                'pending' => 'warning',
                                'syncing' => 'info',
                                default => 'secondary',
                            };
                        @endphp
                        <tr>
                            <td>
                                #{{ $space->offering_id }}<br>
                                <span class="text-muted small">{{ $space->offering?->name ?? 'Unnamed offering' }}</span>
                            </td>
                            <td>{{ $space->property?->name ?? $space->offering?->property?->name ?? 'Unknown' }}</td>
                            <td><span class="badge bg-{{ $badge }}">{{ ucfirst($space->status) }}</span></td>
                            <td>
                                @if($space->decidim_assembly_url)
                                    <a href="{{ $space->decidim_assembly_url }}" target="_blank" rel="noopener">
                                        {{ $space->decidim_assembly_slug ?: 'Open Assembly' }}
                                    </a>
                                @else
                                    <span class="text-muted">Not created</span>
                                @endif
                            </td>
                            <td class="small">
                                <div>Debates: {{ $space->decidim_debates_component_id ?: '—' }}</div>
                                <div>Proposals: {{ $space->decidim_proposals_component_id ?: '—' }}</div>
                                <div>Meetings: {{ $space->decidim_meetings_component_id ?: '—' }}</div>
                                <div>Voting: {{ $space->decidim_voting_component_id ?: 'placeholder / not installed' }}</div>
                            </td>
                            <td>{{ $syncCounts[$space->offering_id] ?? 0 }}</td>
                            <td>{{ $space->retry_count }}</td>
                            <td class="small">
                                <div>Attempt: {{ $space->last_attempted_at?->diffForHumans() ?? '—' }}</div>
                                <div class="text-success">Success: {{ $space->last_succeeded_at?->diffForHumans() ?? '—' }}</div>
                                <div class="text-danger">Failed: {{ $space->last_failed_at?->diffForHumans() ?? '—' }}</div>
                            </td>
                            <td class="text-danger small" style="max-width: 260px;">
                                {{ $space->error_message ? \Illuminate\Support\Str::limit($space->error_message, 160) : '—' }}
                            </td>
                            <td class="text-end">
                                @if($space->offering)
                                    <form method="POST" action="{{ route('admin.decidim.retry', $space->offering) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Retry sync</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-muted p-4">No Decidim governance spaces have been created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($spaces->hasPages())
            <div class="card-footer bg-white">
                {{ $spaces->links() }}
            </div>
        @endif
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Recent failed queue jobs</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Connection</th>
                        <th>Queue</th>
                        <th>Failed at</th>
                        <th>Exception</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($failedJobs as $job)
                        <tr>
                            <td>{{ $job->id ?? $job->uuid ?? '—' }}</td>
                            <td>{{ $job->connection ?? '—' }}</td>
                            <td>{{ $job->queue ?? '—' }}</td>
                            <td>{{ $job->failed_at ?? '—' }}</td>
                            <td class="small text-danger" style="max-width: 520px;">
                                {{ \Illuminate\Support\Str::limit($job->exception ?? '', 220) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted p-4">No failed queue jobs have been recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Settled offerings without Decidim governance</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Offering</th>
                        <th>Property</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($offeringsWithoutSpaces as $offering)
                        <tr>
                            <td>#{{ $offering->id }} — {{ $offering->name }}</td>
                            <td>{{ $offering->property?->name ?? 'Unknown' }}</td>
                            <td><span class="badge bg-success">{{ $offering->status }}</span></td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.decidim.retry', $offering) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Create governance</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted p-4">Every settled offering has a Decidim governance record.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
