<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; // Add this import
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL; // For generating signed URLs
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\HtmlString; 

class TestController extends Controller
{
    public function testPayment()
    {
        $this->sendEmailVerificationEmail();die;
        //https://dashboard.stripe.com/test/apikeys
        $amount = 50; // Amount in dollars
        $currency = 'eur';
        $source = 'tok_visa'; // Test token provided by Stripe
        $description = 'Test payment';

        $stripeController = new StripeController();
        return $stripeController->testhandlePayment($amount, $currency, $source, $description);
    }

    public function sendEmailVerificationEmail()
    {
        $userId = 1; // Replace with an actual user ID from your database
        $user = User::findOrFail($userId);

        // Generate the signed verification URL
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Send email using Laravel's Mail facade
        Mail::send([], [], function ($message) use ($user, $verificationUrl) {
            $message->to($user->email)
                ->subject('Verify Your Email Address')
                ->html(
                    "<p>Hello {$user->username},</p>" .
                    "<p>Please verify your email address by clicking the link below:</p>" .
                    "<p><a href='{$verificationUrl}'>Verify Email</a></p>" .
                    "<p>Thank you!</p>"
                );
        });

        return response()->json(['message' => 'Verification email sent successfully.']);
    }

    public function verifyEmail($id, $hash)
    {
        // Fake user ID and hash for testing
        $userId = $id; // Replace with an actual user ID from your database
        $emailHash = $hash; // Replace with a valid hash for testing

        // Retrieve the user by ID
        $user = User::findOrFail($userId);

        // Check if the hash matches the user's email
        if (sha1($user->email) !== $emailHash) {
            return response()->json(['error' => 'Invalid verification link.'], 400);
        }

        // Mark the email as verified if not already verified
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email verified successfully.']);
    }
}
