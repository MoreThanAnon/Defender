<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use Illuminate\Http\Request;
use App\Models\Deposits;
use App\Models\withdrawal;
use App\Models\User;
use App\Models\Bids;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $auth = auth()->user()->id;
        $availableCash = User::where('id', operator: $auth)->value('funds');
        $totalDeposits = Deposit::where('user_id', operator: $auth)->sum('net_amount');
        $totalWithdrawals = withdrawal::where('user_id', operator: $auth)->sum('net_amount');
        // Fetch data from the database
        //MVP for now
     // Fetch total invested from bids where status is 'complete'
        $totalInvested = Bids::where('user_id', $auth)
        ->where('status', 'complete')
        ->sum('amount');
        $principalRepaid = 2057.96;
        $interestEarned = 354.74;
        $investorFee = 38.57;

        //Add these in future
        $loanSupport = 0;
        $liveInvestments = 0.00;
        $balanceFee = 0.03;
        $principalOutstanding = 0.00;
        //Remove in general:
        $finishedLoansInvested = 2000.00;
        $finishedLoansEarned = 338.68;
        $badDebtLoansInvested = 100.00;
        $badDebtLoansRepaid = 57.96;
        $badDebtLoansEarned = 16.06;
        $finishedLoansRepaid = 2000.00;
        $repayingLoansEarned = 0.00;
        $repayingLoansRepaid = 0.00;
        $repayingLoansInvested = 0.00;
        $remainingBalance = 74.58;//Wtf is this

        $recentActivities = [
            'Monthly repayment of €4.32 by Dundalk Bay Sea Foods Limited (8 months ago)',
            'Monthly repayment of €4.32 by Dundalk Bay Sea Foods Limited (9 months ago)',
            'Monthly repayment of €4.31 by Dundalk Bay Sea Foods Limited (10 months ago)',
            'Monthly repayment of €4.31 by Dundalk Bay Sea Foods Limited (a year ago)',
            'Monthly repayment of €4.30 by Dundalk Bay Sea Foods Limited (a year ago)',
        ];

        $currentFund = 5000; // Example value
        $targetFund = 10000; // Example value
        $candidates = [
            (object) ['id' => 1, 'name' => 'Candidate 1'],
            (object) ['id' => 2, 'name' => 'Candidate 2'],
            (object) ['id' => 3, 'name' => 'Candidate 3'],
        ];

        return view('home', [
            'totalDeposits' => $totalDeposits,
            'totalWithdrawals' => $totalWithdrawals,
            'loanSupport' => $loanSupport,
            'totalInvested' => $totalInvested,
            'principalRepaid' => $principalRepaid,
            'interestEarned' => $interestEarned,
            'availableCash' => $availableCash,
            'liveInvestments' => $liveInvestments,
            'investorFee' => $investorFee,
            'balanceFee' => $balanceFee,
            'remainingBalance' => $remainingBalance,
            'recentActivities' => $recentActivities,
            'principalOutstanding' => $principalOutstanding,
            'repayingLoansInvested' => $repayingLoansInvested,
            'repayingLoansRepaid' => $repayingLoansRepaid,
            'repayingLoansEarned' => $repayingLoansEarned,
            'finishedLoansInvested' => $finishedLoansInvested,
            'finishedLoansRepaid' => $finishedLoansRepaid,
            'finishedLoansEarned' => $finishedLoansEarned,
            'badDebtLoansInvested' => $badDebtLoansInvested,
            'badDebtLoansRepaid' => $badDebtLoansRepaid,
            'badDebtLoansEarned' => $badDebtLoansEarned,
            'currentFund' => $currentFund,
            'targetFund' => $targetFund,
            'candidates' => $candidates,
        ]);
    }


}
