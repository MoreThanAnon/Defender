<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Investment;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalEquity = Investment::where('type', 'equity')->sum('amount');
        $totalLoan = Investment::where('type', 'loan')->sum('amount');
        $totalInvested = $totalEquity + $totalLoan;

        $activeLoans = Investment::where('type', 'loan')->where('status', 'active')->sum('amount');
        $paidLoans = Investment::where('type', 'loan')->where('status', 'paid')->sum('amount');
        $inactiveLoans = Investment::where('type', 'loan')->where('status', 'inactive')->sum('amount');

        // You can replace with real logic later
        $avgProfit = 0;

        return view('admin.dashboard', compact(
            'totalEquity',
            'totalLoan',
            'totalInvested',
            'activeLoans',
            'paidLoans',
            'inactiveLoans',
            'avgProfit'
        ));
    }
}
