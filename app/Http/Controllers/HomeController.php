<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        // Fetch data from the database
        $totalDeposits = 800.00;
        $totalWithdrawals = 999.52;
        $loanSupport = 32;
        $totalInvested = 2100.00;
        $principalRepaid = 2057.96;
        $interestEarned = 354.74;
        $availableCash = 74.58;
        $liveInvestments = 0.00;
        $investorFee = 38.57;
        $balanceFee = 0.03;
        $remainingBalance = 74.58;
        $principalOutstanding = 0.00;
        $repayingLoansInvested = 0.00;
        $repayingLoansRepaid = 0.00;
        $repayingLoansEarned = 0.00;
        $finishedLoansInvested = 2000.00;
        $finishedLoansRepaid = 2000.00;
        $finishedLoansEarned = 338.68;
        $badDebtLoansInvested = 100.00;
        $badDebtLoansRepaid = 57.96;
        $badDebtLoansEarned = 16.06;
        $recentActivities = [
            'Monthly repayment of €4.32 by Dundalk Bay Sea Foods Limited (8 months ago)',
            'Monthly repayment of €4.32 by Dundalk Bay Sea Foods Limited (9 months ago)',
            'Monthly repayment of €4.31 by Dundalk Bay Sea Foods Limited (10 months ago)',
            'Monthly repayment of €4.31 by Dundalk Bay Sea Foods Limited (a year ago)',
            'Monthly repayment of €4.30 by Dundalk Bay Sea Foods Limited (a year ago)',
        ];

        return view('home', compact(
            'totalDeposits', 'totalWithdrawals', 'loanSupport', 'totalInvested', 'principalRepaid', 'interestEarned',
            'availableCash', 'liveInvestments', 'investorFee', 'balanceFee', 'remainingBalance', 'principalOutstanding',
            'repayingLoansInvested', 'repayingLoansRepaid', 'repayingLoansEarned', 'finishedLoansInvested',
            'finishedLoansRepaid', 'finishedLoansEarned', 'badDebtLoansInvested', 'badDebtLoansRepaid', 'badDebtLoansEarned',
            'recentActivities'
        ));
    }
}
