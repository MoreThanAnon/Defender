<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $deposits = $user->deposits()->orderBy('created_at', 'desc')->get();
        $withdrawals = $user->withdrawals()->orderBy('created_at', 'desc')->get();

        return view('transactions.index', compact('deposits', 'withdrawals'));
    }
}
