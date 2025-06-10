@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h1 class="mb-4">Admin Dashboard</h1>
    <div class="row">
        <div class="col-md-6">
            <div class="card p-3 mb-3 shadow-sm">
                <h5>Total Equity Investments</h5>
                <p class="fw-bold text-success">€{{ number_format($totalEquity, 2) }}</p>
            </div>
            <div class="card p-3 mb-3 shadow-sm">
                <h5>Total Loan Investments</h5>
                <p class="fw-bold text-info">€{{ number_format($totalLoan, 2) }}</p>
            </div>
            <div class="card p-3 mb-3 shadow-sm">
                <h5>Total Invested (Equity + Loan)</h5>
                <p class="fw-bold">€{{ number_format($totalInvested, 2) }}</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3 mb-3 shadow-sm">
                <h5>Loan Status Summary</h5>
                <p>Active Loans: €{{ number_format($activeLoans, 2) }}</p>
                <p>Paid Loans: €{{ number_format($paidLoans, 2) }}</p>
                <p>Inactive Loans: €{{ number_format($inactiveLoans, 2) }}</p>
            </div>
            <div class="card p-3 mb-3 shadow-sm">
                <h5>Average User Profit</h5>
                <p class="text-muted">€{{ number_format($avgProfit, 2) }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
