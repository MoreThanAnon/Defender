<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
class AuthController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {       
        $request->validate([
            'username' => 'required|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'username' => $request->input('username'),
            'password' => bcrypt($request->input('password')),
            'updated_at' => now(),
            'created_at' => now(),
            'email' => $request->input('email')
        ]);

        return redirect()->route('login')->with('success', 'Registration successful!');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

public function login(Request $request)
{
    $this->validate($request, [
        'email' => 'required|email',
        'password' => 'required|string'
    ]);

    $credentials = $request->only('email', 'password');
    if (!$token = JWTAuth::attempt($credentials)) {
        return redirect()->back()->withErrors([
            'email' => 'Invalid credentials'
        ])->withInput();
    }

    // Store token in session
    session(['jwt_token' => $token]);
    $user = Auth::user(); // This will be set by JWTAuth::attempt

    Auth::login($user);
    return redirect()->route('home')->with('success', 'Login successful!');
}

    public function dashboard()
    {
        return view('home');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}