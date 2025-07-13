@extends('layouts.app')

@section('content')
<div class="container py-4">

    <h2 class="mb-4">My Transactions</h2>

    <h4>Deposits</h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Gross Amount</th>
                <th>Site Fee</th>
                <th>Net Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($deposits as $deposit)
                <tr>
                    <td>{{ $deposit->created_at->format('Y-m-d') }}</td>
                    <td>{{ number_format($deposit->gross_amount, 2) }}</td>
                    <td>{{ number_format($deposit->site_fee, 2) }}</td>
                    <td>{{ number_format($deposit->net_amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No deposits found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h4 class="mt-5">Withdrawals</h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Gross Amount</th>
                <th>Site Fee</th>
                <th>Net Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($withdrawals as $withdrawal)
                <tr>
                    <td>{{ $withdrawal->created_at->format('Y-m-d') }}</td>
                    <td>{{ number_format($withdrawal->gross_amount, 2) }}</td>
                    <td>{{ number_format($withdrawal->site_fee, 2) }}</td>
                    <td>{{ number_format($withdrawal->net_amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No withdrawals found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
