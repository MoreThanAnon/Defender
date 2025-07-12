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
        if (!app()->environment('local')) {
            abort(404);
        }

        $this->testMe("D02X285");die;
        $this->sendEmailVerificationEmail();die;
        //https://dashboard.stripe.com/test/apikeys
        $amount = 50; // Amount in dollars
        $currency = 'eur';
        $source = 'tok_visa'; // Test token provided by Stripe
        $description = 'Test payment';

        $stripeController = new StripeController();
        return $stripeController->testhandlePayment($amount, $currency, $source, $description);
    }

    public function testMe($eircode)
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        $output = [];
        $returnCode = 0;

        // Debug: Log the command being executed
        $command = 'C:\Users\DELL\AppData\Local\Programs\Python\Python37-32\python.exe C:\wamp644\www\defender\storage\scripts\location.py "https://www.google.com/maps/place/' . $eircode . '" 2>&1';
        echo "Executing command: $command\n";

        exec($command, $output, $returnCode);

        // Debug: Log the output and return code
        echo "Command output:\n";
        var_dump($output);

        echo "Return code: $returnCode\n";

        if ($returnCode !== 0) {
            echo "An error occurred while executing the script. Return code: $returnCode\n";
            var_dump($output);
        } else {
            echo "Script executed successfully. Output:\n";
            var_dump($output);
        }
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
