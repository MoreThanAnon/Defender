<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Charge;
use App\Models\ChargeLog;
use App\Models\User;
use App\Models\Deposit;
use Illuminate\Support\Facades\Auth;

class StripeController extends Controller
{
    public function showPaymentForm()
    {
        return view('payment');
    }

    public function handlePayment(Request $request)
    {
        $key = "sk_test_REMOVED";
        Stripe::setApiKey($key);;
        $user = $request->user();

        $chargeRequest = [
            'amount' => $request->amount * 100, // Amount in cents
            'currency' => 'usd',
            'source' => "tok_mastercard",
            'description' => 'Payment from user ID ' . $user->id,
        ];

        try {
            $charge = Charge::create($chargeRequest);

            $chargeResponse = [
                'status' => $charge->status,
                'id' => $charge->id,
                'amount' => $charge->amount,
                'currency' => $charge->currency,
                'description' => $charge->description,
            ];

            ChargeLog::create([
                'request' => json_encode($chargeRequest),
                'response' => json_encode($chargeResponse),
            ]);

            if ($charge->status === 'succeeded') {
                $this->updateDeposit($user, $request->amount);
                return response()->json(['success_message' => 'Payment successful!']);
            } else {
                return response()->json(['error_message' => 'Error! Payment failed.'], 500);
            }

        } catch (\Exception $e) {
            ChargeLog::create([
                'request' => json_encode($chargeRequest),
                'response' => json_encode(['error_message' => $e->getMessage()]),
            ]);

            return response()->json(['error_message' => 'Error! ' . $e->getMessage()], 500);
        }
    }

    public function updateDeposit(User $user, $amount)
    {
        return;
        // We're going to have to calculate the percentage taken from the payment to stripe
        //Then the percentage to us to keep the site going
        //Then most likely create a view to get the customers 
        
        Deposit::create([
            'user_id' => $user->id,
            'gross_amount' => $amount,
        ]);

        // Update user funds
        $user->funds += $amount;
        $user->save();
    }

    public function testhandlePayment($amount, $currency, $source, $userId)
    {
        $key = "sk_test_REMOVED";
        Stripe::setApiKey($key);;

        $chargeRequest = [
            'amount' => $amount * 100,
            'currency' => $currency,
            'source' => $source,
            'description' => 'Payment from user ID ' . $userId,
        ];

        try {
            $charge = Charge::create($chargeRequest);

            $chargeResponse = [
                'status' => $charge->status,
                'id' => $charge->id,
                'amount' => $charge->amount,
                'currency' => $charge->currency,
                'description' => $charge->description,
            ];

            ChargeLog::create([
                'request' => json_encode($chargeRequest),
                'response' => json_encode($chargeResponse),
            ]);

            if ($charge->status === 'succeeded') {
                $user = User::findOrFail($userId);
                $this->updateDeposit($user, $amount);
                return response()->json(['success_message' => 'Payment successful!']);
            } else {
                return response()->json(['error_message' => 'Error! Payment failed.'], 500);
            }

        } catch (\Exception $e) {
            ChargeLog::create([
                'request' => json_encode($chargeRequest),
                'response' => json_encode(['error_message' => $e->getMessage()]),
            ]);

            return response()->json(['error_message' => 'Error! ' . $e->getMessage()], 500);
        }
    }
}
