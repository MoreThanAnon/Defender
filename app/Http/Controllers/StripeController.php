<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\SetupIntent;
use App\Models\ChargeLog;
use App\Models\User;
use App\Models\Deposit;
use App\Models\withdrawal;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET')); // Ensure STRIPE_SECRET is set in your .env file
    }

    public function getCardDetails(Request $request)
    {
        $user = auth()->user();
        $default = $user->defaultPaymentMethod();
        if (!$user->stripe_id) {
            return response()->json(['error' => 'No default card found'], 404);
        }

        return response()->json([
            'success'=> true,
            'brand' => $default->card->brand ?? '',
            'last4' => $default->card->last4 ?? '',
            'exp_month' => $default->card->exp_month ?? '',
            'exp_year' => $default->card->exp_year ?? '',
        ]); 
    }

    
    public function showAddCardForm()
    {
        $intent = auth()->user()->createSetupIntent();
        return view('stripe.add_card', ['intent' => $intent]);
    }

    public function storeCard(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);
    
        $user = auth()->user();
    
        try {
            // Attach the payment method and set it as default
            $user->addPaymentMethod($request->payment_method);
            $user->updateDefaultPaymentMethod($request->payment_method);
    
            return redirect()->route('card.add')->with('success', 'Card saved successfully!');
        } catch (\Exception $e) {
            return redirect()->route('card.add')->with('error', 'Failed to save card: ' . $e->getMessage());
        }
    }

    public function showDepositForm()
    {
        $user = auth()->user();

        // Check if the user is authenticated
        if (!$user) {
            return redirect()->route('login')->with('error', 'You must be logged in to access this page.');
        }

        return view('stripe.deposit', [
            'hasDefault' => $user->hasDefaultPaymentMethod(),
            'defaultPaymentMethodId' => $user->defaultPaymentMethod()?->id,
        ]);
    }

    public function showWithdrawForm()
    {
        return view('stripe.withdraw');
    }

    public function processDeposit(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $user = auth()->user(); // Get the authenticated user

            // Ensure the user has a Stripe customer ID
            if (!$user->stripe_id) {
                $user->createOrGetStripeCustomer();
            }

            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount * 100, // Convert to cents
                'currency' => 'eur',
                'customer' => $user->stripe_id, // Include the customer ID
                'payment_method' => $request->payment_method,
                'confirmation_method' => 'automatic', // Ensure this is set to 'automatic'
                'confirm' => true,
                'return_url' => route('payment.complete'), // Provide a return URL for redirect-based methods
            ]);

            if ($paymentIntent->status === 'succeeded') {
                $this->updateDeposit($user, $request->amount);
            }

            return response()->json([
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'new_balance' => $this->calculateNewBalance($request->amount), // Replace with your logic
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function processWithdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $user = auth()->user();

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

        withdrawal::create([
            'user_id' => $user->id,
            'gross_amount' => $amount,
            'site_fee' => $siteFee,
            'net_amount' => $netAmount,
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'new_balance' => $user->balance,
        ]);
    }

    public function paymentComplete(Request $request)
    {
        return view('payment_complete');
    }

    public function updateDeposit(User $user, $amount)
    {
        $siteFee = round($amount * 0.025, 2); // example: 2.5% fee
    
        Deposit::create([
            'user_id' => $user->id,
            'gross_amount' => $amount,
            'site_fee' => $siteFee,
            'net_amount' => $amount - $siteFee,
        ]);
    }

    public function createSetupIntent(Request $request)
    {
        $user = auth()->user();

        // Ensure the user is a Stripe customer
        $user->createOrGetStripeCustomer();

        // Create a SetupIntent
        $setupIntent = SetupIntent::create([
            'customer' => $user->stripe_id,
        ]);

        return response()->json([
            'client_secret' => $setupIntent->client_secret,
        ]);
    }

    public function deleteCard(Request $request)
    {
        $user = auth()->user();
        // Check if the user has a Stripe customer ID
        if (!$user->stripe_id) {
           // return redirect()->back()->with('error', 'No card on file.');
        }

        $stripe = new StripeClient(env('STRIPE_SECRET'));

        // Validate the card type from the request
        $request->validate([
            'card_type' => 'required|string',
        ]);

        // Retrieve all payment methods for the customer based on the card type
        $paymentMethods = $stripe->paymentMethods->all([
            'customer' => $user->stripe_id,
            'type' => $request->input('card_type'),
        ]);

        //{"object":"list","data":[{"id":"pm_1RKnMxG2co1PzDgDjGJ1gLbf","object":"payment_method","allow_redisplay":"unspecified","billing_details":{"address":{"city":"dublin","country":"IE","line1":"Block D Gleann na heorna","line2":null,"postal_code":"33333","state":"Tallaght"},"email":"anon@anon.com","name":"anon","phone":null,"tax_id":null},"card":{"brand":"visa","checks":{"address_line1_check":"pass","address_postal_code_check":"pass","cvc_check":"pass"},"country":"US","display_brand":"visa","exp_month":3,"exp_year":2033,"fingerprint":"gLkh7w87KCi14iDT","funding":"credit","generated_from":null,"last4":"4242","networks":{"available":["visa"],"preferred":null},"regulated_status":"unregulated","three_d_secure_usage":{"supported":true},"wallet":null},"created":1746306791,"customer":"cus_SFHrLyBO4XrPWp","livemode":false,"metadata":[],"type":"card"}],"has_more":false,"url":"\/v1\/payment_methods"}
        if (!empty($paymentMethods->data) && count($paymentMethods->data) > 0) {
            // Detach the first payment method (default card)
            $stripe->paymentMethods->detach($paymentMethods->data[0]->id);

            // Clear the user's default payment method details in the database
            $user->update([
                'pm_type' => null,
                'pm_last_four' => null,
            ]);

            return redirect()->back()->with('success', 'Card deleted successfully.');
        } else {
            return redirect()->back()->with('error', 'No card found to delete.');
        }
    }

    private function calculateNewBalance($depositAmount)
    {
        $user = auth()->user();

        // Fetch the user's current balance (assuming you have a `balance` field in the `users` table)
        $currentBalance = $user->balance ?? 0;

        // Add the deposit amount to the current balance
        $newBalance = $currentBalance + $depositAmount;

        // Update the user's balance in the database
        $user->update(['balance' => $newBalance]);

        return $newBalance;
    }
}
