<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\withdrawal;
use Illuminate\Support\Facades\Auth;

class WithdrawalController extends Controller
{
    public function process(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $user = Auth::user();

        if ($user->balance < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance.',
            ]);
        }

        $amount = $request->amount;
        $siteFee = round($amount * 0.025, 2);
        $netAmount = $amount - $siteFee;

        $user->balance -= $amount;
        $user->save();

        $withdrawal = withdrawal::create([
            'user_id' => $user->id,
            'gross_amount' => $amount,
            'site_fee' => $siteFee,
            'net_amount' => $netAmount,
            'created_at' => now(),
        ]);

        if (!$withdrawal) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record withdrawal.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'new_balance' => $user->balance,
        ]);
    }
}
