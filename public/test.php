<?php

namespace App\Http\Controllers\PublicArea;

use App\Http\Controllers\Controller; // Add this import
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\HtmlString; 

class TestsController extends Controller
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
        // Fake user ID for testing
        $userId = 1; // Replace with an actual user ID from your database
    
        // Retrieve the user by ID
        $user = User::findOrFail($userId);
    
        // Generate the verification URL with the user's ID and a hash of their email
        $verificationUrl = route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]);
    
        // Create a Symfony Email instance
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($user->email)
            ->subject('Verify Your Email Address')
            ->html(
                "Hello {$user->username},<br><br>" .
                "Please verify your email address by clicking the link below:<br>" .
                "<a href='{$verificationUrl}'>Verify Email</a><br><br>" .
                "Thank you!"
            );
    
        // Create a Symfony Mailer instance and send the email
        $transport = Transport::fromDsn(env('MAIL_MAILER_DSN'));
        $mailer = new Mailer($transport);
        $mailer->send($email);
    
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
