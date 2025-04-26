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
    public function showDepositFunds()
    {
        $user = auth()->user();

        $intent = auth()->user()->createSetupIntent();
        return view('deposit_funds',['intent' => $intent]); // Ensure this view exists
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

    public function widthdrawl(Request $request){
        //Get user
        //Get
    }
    public function storePaymentMethod(Request $request)
    {
        $user = auth()->user();

        try {
            if (!$user->hasStripeId()) {
                $user->createAsStripeCustomer();
            }

            $chargeRequest = [
                'action' => 'store_payment_method',
                'user_id' => $user->id,
                'payment_method' => $request->payment_method,
                'billing' => $request->only(['name', 'email', 'line1', 'city', 'state', 'postal_code', 'country']),
            ];

            // Save and set default method
            $user->charge(5000, $request->payment_method); // Charge $50
            $user->updateDefaultPaymentMethod($request->payment_method);

            // Optional: attach billing address to Stripe customer
            $user->updateStripeCustomer([
                'name' => $request->name,
                'email' => $request->email,
                'address' => [
                    'line1' => $request->line1,
                    'city' => $request->city,
                    'state' => $request->state,
                    'postal_code' => $request->postal_code,
                    'country' => $request->country,
                ]
            ]);

            $chargeResponse = [
                'status' => 'success',
                'default_payment_method' => $user->defaultPaymentMethod()?->id,
            ];

            ChargeLog::create([
                'request' => json_encode($chargeRequest),
                'response' => json_encode($chargeResponse),
            ]);

            return redirect()->back()->with('success', 'Card added successfully!');
        } catch (\Exception $e) {
            $chargeResponse = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];

            ChargeLog::create([
                'request' => json_encode($request->all()),
                'response' => json_encode($chargeResponse),
            ]);

            return redirect()->back()->with('error', 'Error saving card: ' . $e->getMessage());
        }
    }
}
