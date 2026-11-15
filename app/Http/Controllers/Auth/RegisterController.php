<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DecidimUserProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request, DecidimUserProvisioningService $decidim)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)],
        ], ['username.regex' => 'Use letters, numbers, dots, dashes or underscores only.']);

        // Password is hashed by the model cast.
        $user = User::create($data);

        // Optional governance integration. It is off by default and must never block sign-up.
        try {
            $decidim->provision($user);
        } catch (\Throwable $exception) {
            report($exception);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('bc.index'));
    }
}
