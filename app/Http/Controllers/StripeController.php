<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Charge;
use App\Models\ChargeLog;
//https://docs.stripe.com/testing#cards
//https://stripe.com/docs/testing
class StripeController extends Controller
{
    public function showPaymentForm()
    {
        return view('payment');
    }

    public function handlePayment(Request $request)
    {
        Stripe::setApiKey(config('sk_test_REMOVED'));

        $chargeRequest = [
            'amount' => $request->amount * 100, // Amount in cents
            'currency' => 'usd',
            'source' => $request->stripeToken,
            'description' => 'Payment from user ID ' . $request->user()->id,
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

            if ($charge->status == 'succeeded') {
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

    public function testhandlePayment($amount, $currency, $source, $userId)
    {
        Stripe::setApiKey('sk_test_REMOVED');

        $chargeRequest = [
            'amount' => $amount * 100, // Amount in cents
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

            if ($charge->status == 'succeeded') {
                return response()->json(['success_message' => 'Payment successful!']);
            } else {
                return response()->json(['error_message' => 'Error! Payment failed.'], 500);
            }
        } catch (\Exception $e) {
            ChargeLog::create([
                'request' => json_encode($chargeRequest),
                'response' => json_encode(['error_message' => $e->getMessage()]),
            ]);

            return response()->json(data: ['error_message' => 'Error! ' . $e->getMessage()], 500);
        }
    }
}
