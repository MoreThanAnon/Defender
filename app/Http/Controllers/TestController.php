<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function testPayment()
    {
        //https://dashboard.stripe.com/test/apikeys
        $amount = 50; // Amount in dollars
        $currency = 'eur';
        $source = 'tok_visa'; // Test token provided by Stripe
        $description = 'Test payment';

        $stripeController = new StripeController();
        return $stripeController->testhandlePayment($amount, $currency, $source, $description);
    }
}
