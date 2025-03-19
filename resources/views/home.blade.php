@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title">Financial Information</h3>
                    <p>Total Deposits: €{{ $totalDeposits }}</p>
                    <p>Total Withdrawals: €{{ $totalWithdrawals }}</p>
                    <p>Loan Support: {{ $loanSupport }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title">Investment Information</h3>
                    <p>Total Invested: €{{ $totalInvested }}</p>
                    <p>Principal Repaid: €{{ $principalRepaid }}</p>
                    <p>Interest Earned: €{{ $interestEarned }}</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title">AutoInvest and Balance</h3>
                    <div class="form-group">
                        <label for="autoInvest">AutoInvest</label>
                        <input
                            type="text"
                            id="autoInvest"
                            class="form-control"
                            oninput="handleAutoInvestChange(event)"
                        />
                        <small id="autoInvestError" class="text-danger"></small>
                    </div>
                    <p>Available Cash: €{{ $availableCash }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title">Funds Overview</h3>
                    <p>Total Deposits: €{{ $totalDeposits }}</p>
                    <p>Total Withdrawals: €{{ $totalWithdrawals }}</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title">Investments Overview</h3>
                    <p>Total Invested: €{{ $totalInvested }}</p>
                    <p>Principal Repaid: €{{ $principalRepaid }}</p>
                    <p>Interest Earned: €{{ $interestEarned }}</p>
                    <p>Live Investments: €{{ $liveInvestments }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title">Deductions</h3>
                    <p>Investor Fee: €{{ $investorFee }}</p>
                    <p>Balance Fee: €{{ $balanceFee }}</p>
                    <p>Remaining Balance: €{{ $remainingBalance }}</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title">Recent Activity</h3>
                    <ul>
                        @foreach ($recentActivities as $activity)
                            <li>{{ $activity }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title">Account Value</h3>
                    <p>Principal Outstanding: €{{ $principalOutstanding }}</p>
                    <p>Live Investments: €{{ $liveInvestments }}</p>
                    <p>Available Cash: €{{ $availableCash }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title">Investor Portfolio</h3>
                    <p>Loans Supported: {{ $loanSupport }}</p>
                    <p>0 Repaying Loans: €{{ $repayingLoansInvested }} invested, €{{ $repayingLoansRepaid }} repaid, €{{ $repayingLoansEarned }} earned</p>
                    <p>30 Finished Loans: €{{ $finishedLoansInvested }} invested, €{{ $finishedLoansRepaid }} repaid, €{{ $finishedLoansEarned }} earned</p>
                    <p>2 Bad Debt Loans: €{{ $badDebtLoansInvested }} invested, €{{ $badDebtLoansRepaid }} repaid, €{{ $badDebtLoansEarned }} earned</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function handleAutoInvestChange(e) {
        const value = e.target.value;
        let autoInvestError = '';
        if (parseFloat(value) > {{ $availableCash }}) {
            autoInvestError = 'Balance is too low';
        }
        document.getElementById('autoInvestError').innerText = autoInvestError;
    }
</script>
@endsection
