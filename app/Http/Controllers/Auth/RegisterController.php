<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Verified;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest');
    }
    public function showRegisterForm()
    {
        return view('auth.register');
    }
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users',
            'password' => 'required|min:6',
            'email' => 'required|email|unique:users',
        ]);

        $user = User::create([
            'username' => $request->input('username'),
            'password' => bcrypt($request->input('password')),
            'email' => $request->input('email'),
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        // Send verification email
        //$this->sendEmailVerificationEmail($user);

        return redirect()->route('login')->with('success', 'Registration successful! Please verify your email.');
    }
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make(value: $data['password']),
        ]);
    }

    protected function sendEmailVerificationEmail($user)
    {
        return;
        // Generate the verification URL with the user's ID and a hash of their email
        $verificationUrl = route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]);

        // Use Laravel's Mail facade to send an email
        Mail::send([], [], function ($message) use ($user, $verificationUrl) {
            $message->to($user->email)
                ->subject('Verify Your Email Address')
                ->setBody(
                    "Hello {$user->username},<br><br>" .
                    "Please verify your email address by clicking the link below:<br>" .
                    "<a href='{$verificationUrl}'>Verify Email</a><br><br>" .
                    "Thank you!",
                    'text/html' // Specify that the email body is in HTML format
                );
        });
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (sha1($user->email) !== $hash) {
            return redirect()->route('login')->with('error', 'Invalid verification link.');
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect()->route('login')->with('success', 'Email verified successfully!');
    }
}
