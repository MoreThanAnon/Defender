@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title text-primary fw-bold">Financial Information</h5>
                    <p class="mb-1"><strong>Available Cash:</strong> EUR {{ number_format($availableCash, 2) }}</p>
                    <p class="mb-1"><strong>Total Deposits:</strong> EUR {{ number_format($totalDeposits, 2) }}</p>
                    <p class="mb-0"><strong>Total Withdrawals:</strong> EUR {{ number_format($totalWithdrawals, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title text-success fw-bold">Investment Information</h5>
                    <p class="mb-1"><strong>Total Invested:</strong> EUR {{ number_format($totalInvested, 2) }}</p>
                    <p class="mb-1"><strong>Principal Repaid:</strong> EUR {{ number_format($principalRepaid, 2) }}</p>
                    <p class="mb-0"><strong>Interest Earned:</strong> EUR {{ number_format($interestEarned, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h5 class="card-title text-info fw-bold mb-0">My Investments</h5>
                        <span class="text-muted small">{{ $investments->count() }} active holding(s)</span>
                    </div>

                    @if($investments->isEmpty())
                        <p class="text-muted mb-0">You do not have any settled investments yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th class="text-end">Ownership</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-end">Interest</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($investments as $investment)
                                        <tr>
                                            <td>
                                                @if($investment->property)
                                                    <a href="{{ route('property.show', $investment->property->id) }}">
                                                        {{ $investment->property->name }}
                                                    </a>
                                                @else
                                                    Property #{{ $investment->property_id }}
                                                @endif
                                            </td>
                                            <td>{{ ucfirst($investment->type) }}</td>
                                            <td>{{ ucfirst($investment->status) }}</td>
                                            <td class="text-end">
                                                @if($investment->type === 'equity' && $ownershipPercentages->has($investment->property_id))
                                                    {{ number_format($ownershipPercentages[$investment->property_id], 4) }}%
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-end">EUR {{ number_format($investment->amount, 2) }}</td>
                                            <td class="text-end">
                                                {{ $investment->interest_rate ? number_format($investment->interest_rate, 2).'%' : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h5 class="card-title fw-bold mb-0">Current Bids</h5>
                        <a href="{{ route('properties.index') }}" class="btn btn-sm btn-outline-primary">Browse Properties</a>
                    </div>

                    @if($currentBids->isEmpty())
                        <p class="text-muted mb-0">You do not have any open or accepted bids.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Status</th>
                                        <th>Allocation</th>
                                        <th class="text-end">Bid Amount</th>
                                        <th class="text-end">Accepted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($currentBids as $bid)
                                        <tr>
                                            <td>
                                                @if($bid->property)
                                                    <a href="{{ route('property.show', $bid->property->id) }}">
                                                        {{ $bid->property->name }}
                                                    </a>
                                                @else
                                                    Property #{{ $bid->property_id }}
                                                @endif
                                            </td>
                                            <td>{{ $bid->status }}</td>
                                            <td>{{ str_replace('_', ' ', ucfirst($bid->allocation_status ?? 'pending')) }}</td>
                                            <td class="text-end">EUR {{ number_format($bid->amount, 2) }}</td>
                                            <td class="text-end">
                                                EUR {{ number_format(((int) ($bid->accepted_amount_cents ?? 0)) / 100, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title text-info fw-bold">Recent Activity</h5>
                    <ul class="list-group list-group-flush">
                        @foreach($recentActivities as $activity)
                            <li class="list-group-item">{{ $activity }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @include('components.footer')
</div>
@endsection
