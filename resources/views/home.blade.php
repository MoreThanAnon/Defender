@extends('layouts.app')

@section('content')
<div class="container py-5">
    @include('components.navbar')

    <div class="row g-4">
        {{-- Financial & Investment Info --}}
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title text-primary fw-bold">Financial Information</h5>
                    <p class="mb-1">💰 <strong>Total Deposits:</strong> €{{ $totalDeposits }}</p>
                    <p class="mb-0">🏦 <strong>Total Withdrawals:</strong> €{{ $totalWithdrawals }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title text-success fw-bold">Investment Information</h5>
                    <p class="mb-1">📈 <strong>Total Invested:</strong> €{{ $totalInvested }}</p>
                    <p class="mb-1">💸 <strong>Principal Repaid:</strong> €{{ $principalRepaid }}</p>
                    <p class="mb-0">💵 <strong>Interest Earned:</strong> €{{ $interestEarned }}</p>
                </div>
            </div>
        </div>

        {{-- Funds & Investments Overview --}}
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title text-secondary fw-bold">Funds Overview</h5>
                    <p class="mb-1"><strong>Total Deposits:</strong> €{{ $totalDeposits }}</p>
                    <p class="mb-0"><strong>Total Withdrawals:</strong> €{{ $totalWithdrawals }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title text-secondary fw-bold">Investments Overview</h5>
                    <p class="mb-1"><strong>Total Invested:</strong> €{{ $totalInvested }}</p>
                    <p class="mb-1"><strong>Principal Repaid:</strong> €{{ $principalRepaid }}</p>
                    <p class="mb-0"><strong>Interest Earned:</strong> €{{ $interestEarned }}</p>
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title text-info fw-bold">Recent Activity</h5>
                    <ul class="list-group list-group-flush">
                        @foreach ($recentActivities as $activity)
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
