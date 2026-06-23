@extends('layouts.app')
@php($hideNavbar = true)

@section('content')
<style>
    /* ===== Palette & tokens (shared with homepage) ===== */
    :root{
        --bg: #f6f7f5;
        --text: #16352b;
        --muted: #ecf3ef;
        --primary: #0f7b5c;
        --primary-600:#0e6f54;
        --primary-700:#0c624a;
        --accent: #ff7a45;
        --white:#ffffff;
        --border: 1px solid rgba(8,40,27,.08);
        --shadow: 0 14px 34px rgba(15,123,92,.18);
        --radius: 18px;
        --radius-lg: 26px;
    }
    @media (prefers-color-scheme: dark){
        :root{
            --bg:#0e1714;
            --text:#e9f3ef;
            --muted:#0f201a;
            --white:#121917;
            --border: 1px solid rgba(255,255,255,.08);
            --shadow: 0 18px 38px rgba(0,0,0,.55);
        }
    }

    /* ===== Page background ===== */
    body{
        background:
            radial-gradient(900px 420px at 10% 0%, rgba(15,123,92,.12), transparent 60%),
            radial-gradient(900px 460px at 110% 100%, rgba(255,122,69,.12), transparent 60%),
            var(--bg) !important;
        color: var(--text);
    }

    /* ===== Card (glassy) ===== */
    .auth-card{
        background: linear-gradient(180deg, rgba(255,255,255,.85), rgba(255,255,255,.72));
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-radius: var(--radius-lg);
        border: var(--border);
        box-shadow: var(--shadow);
    }

    /* ===== Heading underline (tricolour hint) ===== */
    .auth-title{
        color: var(--primary-700);
        font-weight: 800;
        letter-spacing:.3px;
        position: relative;
    }
    .auth-title:after{
        content:"";
        display:block;
        height:4px; width:92px; margin:.6rem auto 0;
        border-radius:999px;
        background: linear-gradient(90deg, var(--primary) 0 33%, #fff 33% 66%, var(--accent) 66% 100%);
        opacity:.9;
    }

    /* ===== Inputs ===== */
    .form-control{
        border-radius: 12px;
        border: 1px solid rgba(8,40,27,.15);
        background: #fff;
        padding: .9rem 1rem;
        transition: box-shadow .2s ease, border-color .2s ease, background .2s ease;
    }
    .form-control:focus{
        border-color: var(--primary);
        box-shadow: 0 0 0 .25rem rgba(15,123,92,.15);
        background: #fff;
    }
    .form-control.is-invalid{
        border-color: #e55353;
        box-shadow: 0 0 0 .2rem rgba(229,83,83,.12);
    }

    /* ===== Buttons ===== */
    .btn{
        border-radius: 999px;
        font-weight: 700;
        padding: .9rem 1.2rem;
        transition: transform .14s ease, box-shadow .2s ease, background .2s ease, color .2s ease;
    }
    .btn:active{ transform: translateY(1px) scale(.99); }

    .btn-primary{
        background:#0fb387 linear-gradient(180deg, #16b993, #0f7b5c) !important;
        border-color: var(--primary) !important;
        box-shadow: 0 10px 24px rgba(15,123,92,.28);
    }
    .btn-primary:hover{
        background: linear-gradient(180deg, var(--accent), #e96836) !important;
        border-color: var(--accent) !important;
        box-shadow: 0 12px 28px rgba(255,122,69,.35);
    }

    /* Links */
    .auth-link{
        color: var(--primary);
        font-weight: 600;
        text-decoration: none;
    }
    .auth-link:hover{ color: var(--accent); text-decoration: underline; }

    /* Checkbox */
    .form-check-input:checked{
        background-color: var(--primary);
        border-color: var(--primary);
    }

    /* Alerts */
    .alert-danger{
        border-radius: 12px;
        border: 1px solid rgba(229,83,83,.25);
        box-shadow: 0 6px 18px rgba(229,83,83,.12);
    }
</style>

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-sm-10 col-md-7 col-lg-5">
            <div class="auth-card card p-4 p-md-5">
                <h2 class="text-center mb-4 auth-title">{{ __('Login') }}</h2>
                <p class="text-center text-muted small mb-4">
                    Sign in to Defender. Governance opens from Defender after you are signed in; participants never need a separate Decidim login.
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="username" class="form-label">{{ __('Username or email') }}</label>
                        <input id="username" type="text"
                               class="form-control form-control-lg @error('username') is-invalid @enderror"
                               name="username" value="{{ old('username') }}" required autofocus
                               autocomplete="username">
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">{{ __('Password') }}</label>
                        <input id="password" type="password"
                               class="form-control form-control-lg @error('password') is-invalid @enderror"
                               name="password" required autocomplete="current-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember"
                               {{ old('remember') ? 'checked' : '' }}>
        <label class="form-check-label" for="remember">{{ __('Remember Me') }}</label>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">{{ __('Login') }}</button>
                    </div>

                    @if (Route::has('password.request'))
                        <div class="text-center mb-2">
                            <a href="{{ route('password.request') }}" class="auth-link">
                                {{ __('Forgot Your Password?') }}
                            </a>
                        </div>
                    @endif

                    <div class="text-center">
                        <a href="{{ route('register') }}" class="auth-link">
                            {{ __("Don't have an account? Register here") }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
