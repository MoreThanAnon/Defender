<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'username' => 'required|string|max:255|unique:users',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                             ->withErrors($validator)
                             ->withInput();
        }

        // Create user with an email verification token
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verification_token' => bin2hex(random_bytes(32)),
        ]);

        // Send verification email
        $this->sendEmailConfirmation($user);

        return redirect()->route('login')->with('status', 'Registration successful! Please check your email to verify your account.');
    }

    public function sendEmailConfirmation($user)
    {
        $mail = new PHPMailer(true);

        try {
            // SMTP Server Settings
            $mail->isSMTP();
            $mail->Host       = '127.0.0.1'; // hMailServer IP
            $mail->SMTPAuth   = false;
            $mail->Username   = 'user@localhost.com';
            $mail->Password   = 'anons';
            $mail->Port       = 25;
            $mail->SMTPSecure = '';

            // Email Headers
            $mail->setFrom('user@localhost.com', 'Your Name');
            $mail->addAddress($user->email);
            $mail->Subject = 'Verify Your Email Address';
            $mail->Body    = "Hello {$user->username},\n\nPlease verify your email address by clicking the link below:\n\n" . url("/verify-email/{$user->email_verification_token}") . "\n\nThank you!";

            $mail->send();
        } catch (Exception $e) {
            // Handle email sending error
            error_log("Failed to send verification email: " . $mail->ErrorInfo);
        }
    }
}
