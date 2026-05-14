<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\User;
use Illuminate\Http\Request;
use Stripe\PaymentIntent;
use Stripe\SetupIntent;
use Stripe\Stripe;
use Stripe\StripeClient;

class StripeController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function getCardDetails(Request $request)
    {
        $user = auth()->user();
        $default = $user->defaultPaymentMethod();

        if (! $user->stripe_id || ! $default) {
            return response()->json(['error' => 'No default card found'], 404);
        }

        return response()->json([
            'success' => true,
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

        if (! $user) {
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
            $user = auth()->user();

            if (! $user->stripe_id) {
                $user->createOrGetStripeCustomer();
            }

            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount * 100,
                'currency' => 'eur',
                'customer' => $user->stripe_id,
                'payment_method' => $request->payment_method,
                'confirmation_method' => 'automatic',
                'confirm' => true,
                'return_url' => route('payment.complete'),
            ]);

            if ($paymentIntent->status === 'succeeded') {
                $this->updateDeposit($user, $request->amount);
            }

            return response()->json([
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'new_balance' => $this->calculateNewBalance($request->amount),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function paymentComplete(Request $request)
    {
        return view('payment_complete');
    }

    public function updateDeposit(User $user, $amount)
    {
        $siteFee = round($amount * 0.025, 2);

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
        $user->createOrGetStripeCustomer();

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
        $stripe = new StripeClient(env('STRIPE_SECRET'));

        $request->validate([
            'card_type' => 'required|string',
        ]);

        $paymentMethods = $stripe->paymentMethods->all([
            'customer' => $user->stripe_id,
            'type' => $request->input('card_type'),
        ]);

        if (! empty($paymentMethods->data) && count($paymentMethods->data) > 0) {
            $stripe->paymentMethods->detach($paymentMethods->data[0]->id);

            $user->update([
                'pm_type' => null,
                'pm_last_four' => null,
            ]);

            return redirect()->back()->with('success', 'Card deleted successfully.');
        }

        return redirect()->back()->with('error', 'No card found to delete.');
    }

    private function calculateNewBalance($depositAmount)
    {
        $user = auth()->user();
        $currentBalance = $user->balance ?? 0;
        $newBalance = $currentBalance + $depositAmount;

        $user->update(['balance' => $newBalance]);

        return $newBalance;
    }
}
