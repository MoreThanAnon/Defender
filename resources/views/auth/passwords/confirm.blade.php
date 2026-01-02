@extends('layouts.app')
@php($hideNavbar = true)

@section('content')
<style>
    /* ===== Shared palette & tokens ===== */
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

    body{
        background:
            radial-gradient(900px 420px at 10% 0%, rgba(15,123,92,.12), transparent 60%),
            radial-gradient(900px 460px at 110% 100%, rgba(255,122,69,.12), transparent 60%),
            var(--bg) !important;
        color: var(--text);
    }

    /* ===== Card (glassy look) ===== */
    .auth-card{
        background: linear-gradient(180deg, rgba(255,255,255,.85), rgba(255,255,255,.72));
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-radius: var(--radius-lg);
        border: var(--border);
        box-shadow: var(--shadow);
    }

    .auth-header{
        background: linear-gradient(90deg, var(--primary), var(--primary-600));
        color:#fff;
        border-radius: var(--radius) var(--radius) 0 0;
        font-weight:700;
        text-align:center;
        letter-spacing:.3px;
        padding: 1rem 0;
        box-shadow: inset 0 -3px 0 var(--accent);
    }

    /* ===== Inputs ===== */
    .form-control{
        border-radius: 12px;
        border: 1px solid rgba(8,40,27,.15);
        background: #fff;
        padding: .9rem 1rem;
        transition: box-shadow .2s ease, border-color .2s ease;
    }
    .form-control:focus{
        border-color: var(--primary);
        box-shadow: 0 0 0 .25rem rgba(15,123,92,.15);
    }
    .form-control.is-invalid{
        border-color: #e55353;
        box-shadow: 0 0 0 .2rem rgba(229,83,83,.12);
    }

    /* ===== Buttons ===== */
    .btn{
        border-radius: 999px;
        font-weight: 700;
        padding: .8rem 1.3rem;
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

    .btn-link{
        color: var(--primary);
        font-weight:600;
        text-decoration:none;
        transition: color .2s ease;
    }
    .btn-link:hover{
        color: var(--accent);
        text-decoration:underline;
    }

    /* ===== Alerts ===== */
    .alert-danger{
        border-radius: 12px;
        border: 1px solid rgba(229,83,83,.25);
        box-shadow: 0 6px 18px rgba(229,83,83,.12);
    }
</style>

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-sm-10 col-md-8 col-lg-6">
            <div class="auth-card card">
                <div class="auth-header">{{ __('Confirm Password') }}</div>

                <div class="card-body p-4 p-md-5">
                    <p class="mb-4 text-center">
                        {{ __('Please confirm your password before continuing.') }}
                    </p>

                    <form method="POST" action="{{ route('password.confirm') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">{{ __('Password') }}</label>
                            <input id="password" type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   name="password" required autocomplete="current-password">
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Confirm Password') }}
                            </button>

                            @if (Route::has('password.request'))
                                <a class="btn btn-link" href="{{ route('password.request') }}">
                                    {{ __('Forgot Your Password?') }}
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
