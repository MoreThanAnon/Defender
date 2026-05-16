@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="mb-0">Admin Dashboard</h1>
        <a href="{{ route('property.create') }}" class="btn btn-success">Create Property</a>
    </div>

    <div class="row mb-4">
        @foreach($adminLinks as $link)
            <div class="col-md-4 mb-3">
                <div class="card h-100 p-3 shadow-sm">
                    <h5>{{ $link['label'] }}</h5>
                    <p class="text-muted mb-3">{{ $link['description'] }}</p>
                    <a href="{{ $link['url'] }}" class="btn btn-{{ $link['class'] }} mt-auto">
                        Open
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card p-3 mb-4 shadow-sm">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h5 class="mb-0">Recent Properties</h5>
            <a href="{{ route('admin.properties.index') }}" class="btn btn-outline-primary btn-sm">View All</a>
        </div>

        @if($recentProperties->isEmpty())
            <p class="text-muted mb-0">No properties have been created yet.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentProperties as $property)
                            <tr>
                                <td>{{ $property->id }}</td>
                                <td>{{ $property->name }}</td>
                                <td>{{ ucfirst($property->type) }}</td>
                                <td>{{ $propertyStatuses[$property->status] ?? 'Unknown' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('property.show', $property->id) }}" class="btn btn-outline-primary btn-sm">View</a>
                                    <a href="{{ route('properties.edit', $property->id) }}" class="btn btn-outline-warning btn-sm">Edit</a>
                                    <a href="{{ route('updates.create', $property->id) }}" class="btn btn-outline-secondary btn-sm">Add Update</a>
                                    <a href="{{ route('proposals.index', $property->id) }}" class="btn btn-outline-info btn-sm">Proposals</a>
                                    @if((int) $property->status === 1)
                                        <form action="{{ route('properties.setStatus', ['id' => $property->id, 'status' => 2]) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-secondary btn-sm">Deactivate</button>
                                        </form>
                                    @else
                                        <form action="{{ route('properties.setStatus', ['id' => $property->id, 'status' => 1]) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-success btn-sm">Activate</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card p-3 mb-3 shadow-sm">
                <h5>Total Equity Investments</h5>
                <p class="fw-bold text-success">EUR {{ number_format($totalEquity, 2) }}</p>
            </div>
            <div class="card p-3 mb-3 shadow-sm">
                <h5>Total Loan Investments</h5>
                <p class="fw-bold text-info">EUR {{ number_format($totalLoan, 2) }}</p>
            </div>
            <div class="card p-3 mb-3 shadow-sm">
                <h5>Total Invested (Equity + Loan)</h5>
                <p class="fw-bold">EUR {{ number_format($totalInvested, 2) }}</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3 mb-3 shadow-sm">
                <h5>Loan Status Summary</h5>
                <p>Active Loans: EUR {{ number_format($activeLoans, 2) }}</p>
                <p>Paid Loans: EUR {{ number_format($paidLoans, 2) }}</p>
                <p>Inactive Loans: EUR {{ number_format($inactiveLoans, 2) }}</p>
            </div>
            <div class="card p-3 mb-3 shadow-sm">
                <h5>Average User Profit</h5>
                <p class="text-muted">EUR {{ number_format($avgProfit, 2) }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
