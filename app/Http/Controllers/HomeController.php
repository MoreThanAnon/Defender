<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use Illuminate\Http\Request;
// use App\Models\Deposits;
use App\Models\withdrawal;
use App\Models\User;
use App\Models\Bid;
use App\Models\EquityPosition;
use App\Models\Investment;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $auth = auth()->user()->id;
        $availableCash = User::where('id', $auth)->value('balance');
        $totalDeposits = Deposit::where('user_id', $auth)->sum('net_amount');
        $totalWithdrawals = withdrawal::where('user_id', $auth)->sum('net_amount');

        $investments = Investment::with('property')
            ->where('user_id', $auth)
            ->orderByDesc('created_at')
            ->get();

        $currentBids = Bid::with('property')
            ->where('user_id', $auth)
            ->whereIn('status', ['pending', 'PLEDGED', 'ACCEPTED', 'accepted'])
            ->orderByDesc('created_at')
            ->get();

        $totalInvested = $investments->where('status', 'active')->sum('amount');
        $ownershipPercentages = EquityPosition::query()
            ->join('offerings', 'offerings.id', '=', 'equity_positions.offering_id')
            ->where('equity_positions.holder_user_id', $auth)
            ->where('equity_positions.position_type', 'LOCAL_INVESTOR')
            ->selectRaw('offerings.property_id, SUM(equity_positions.economic_bp) as economic_bp')
            ->groupBy('offerings.property_id')
            ->pluck('economic_bp', 'property_id')
            ->map(fn ($basisPoints) => round(((int) $basisPoints) / 100, 4));

        $principalRepaid = 2057.96;
        $interestEarned = 0;
        $investorFee = 38.57;

        $recentActivities = [
            'Monthly repayment of €4.32 by Dundalk Bay Sea Foods Limited (8 months ago)',
            'Monthly repayment of €4.32 by Dundalk Bay Sea Foods Limited (9 months ago)',
        ];

        $currentFund = 5000;
        $targetFund = 10000;
        $candidates = [
            (object) ['id' => 1, 'name' => 'Candidate 1'],
            (object) ['id' => 2, 'name' => 'Candidate 2'],
            (object) ['id' => 3, 'name' => 'Candidate 3'],
        ];

        return view('home', [
            'totalDeposits' => $totalDeposits,
            'totalWithdrawals' => $totalWithdrawals,
            'totalInvested' => $totalInvested,
            'principalRepaid' => $principalRepaid,
            'interestEarned' => $interestEarned,
            'availableCash' => $availableCash,
            'investorFee' => $investorFee,
            'recentActivities' => $recentActivities,
            'currentFund' => $currentFund,
            'targetFund' => $targetFund,
            'candidates' => $candidates,
            'investments' => $investments,
            'currentBids' => $currentBids,
            'ownershipPercentages' => $ownershipPercentages,
        ]);
    }
}
