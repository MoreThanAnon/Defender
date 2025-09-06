@extends('layouts.app')

@section('content')
<style>
    body {
        background: linear-gradient(135deg, #169b62 0%, #fff 60%, #ff883e 100%);
        color: #0b6623;
    }
    .hero-section {
        background: linear-gradient(120deg, #169b62 60%, #fff 100%);
        color: #fff;
        min-height: 300px;
        border-bottom: 4px solid #ff883e;
    }
    .btn-primary, .btn-primary:focus {
        background-color: #fff !important;
        border-color: #fff !important;
        color: #169b62 !important;
        font-weight: bold;
    }
    .btn-primary:hover {
        background-color: #ff883e !important;
        border-color: #ff883e !important;
        color: #fff !important;
    }
    .btn-outline-light {
        color: #169b62 !important;
        border-color: #169b62 !important;
        background: #fff !important;
    }
    .btn-outline-light:hover {
        background: #ff883e !important;
        color: #fff !important;
        border-color: #ff883e !important;
    }
    .feature-card {
        border-left: 6px solid #169b62;
        background: #f6fff8;
    }
    .step-number {
        display: inline-block;
        width: 38px;
        height: 38px;
        line-height: 38px;
        border-radius: 50%;
        background: #169b62;
        color: #fff;
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 12px;
    }
    .bg-light {
        background: #e6f4ea !important;
    }
    .footer-irish {
        background: #169b62;
        color: #fff;
        border-radius: 0 0 16px 16px;
        padding: 16px 0;
        text-align: center;
        margin-top: 32px;
        font-size: 15px;
        letter-spacing: 1px;
    }
    .irish-flag {
        display: inline-block;
        width: 36px;
        height: 18px;
        vertical-align: middle;
        margin-left: 8px;
        border-radius: 3px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        background: linear-gradient(90deg, #169b62 0% 33%, #fff 33% 66%, #ff883e 66% 100%);
        border: 1px solid #ccc;
    }
</style>

{{-- Hide navigation links for guests --}}
@auth
    {{-- Navigation for logged-in users (if needed) --}}
@else
    <style>
        .navbar-nav .nav-item {
            display: none !important;
        }
    </style>
@endauth

<div class="hero-section text-center d-flex flex-column justify-content-center align-items-center">
    <div class="container py-5">
        <h1 class="display-4 fw-bold">Protect Local Wealth & Culture</h1>
        <p class="lead mb-4">Invest in Irish property to safeguard your community’s future, keep profits at home, and strengthen our local traditions.</p>
        <div>
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg me-2">Get Started</a>
            <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">Login</a>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row text-center mb-5">
        <h2 class="fw-bold" style="color:#169b62;">Why Choose Us</h2>
        <p class="text-muted">Defend your local interests—our platform empowers you to invest in Irish property, protect your heritage, and ensure that financial benefits stay within our communities.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="feature-card p-4 rounded shadow-sm h-100">
                <h5 class="fw-bold mb-2">Keep Ownership Local</h5>
                <p class="mb-0">Investors from the area receive real equity, helping to preserve Irish ownership and support your neighbours.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card p-4 rounded shadow-sm h-100">
                <h5 class="fw-bold mb-2">Protect Your Interests</h5>
                <p class="mb-0">Choose where your money goes and ensure your investments reflect your values and priorities for Ireland’s future.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card p-4 rounded shadow-sm h-100">
                <h5 class="fw-bold mb-2">Transparency & Control</h5>
                <p class="mb-0">Track your investments, monitor local impact, and stay informed with clear reporting and regular updates.</p>
            </div>
        </div>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container">
        <div class="row text-center mb-4">
            <h2 class="fw-bold" style="color:#ff883e;">How It Works</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <span class="step-number">1</span>
                <h5 class="fw-bold mt-2">Join the Community</h5>
                <p>Register to become part of a network dedicated to protecting Irish assets and culture.</p>
            </div>
            <div class="col-md-4 text-center">
                <span class="step-number">2</span>
                <h5 class="fw-bold mt-2">Invest in Local Projects</h5>
                <p>Direct your funds into Irish properties and developments that matter to you and your community.</p>
            </div>
            <div class="col-md-4 text-center">
                <span class="step-number">3</span>
                <h5 class="fw-bold mt-2">Strengthen Irish Prosperity</h5>
                <p>Watch your investment grow while supporting local jobs, culture, and economic independence.</p>
            </div>
        </div>
    </div>
</div>

<div class="text-center py-5" style="background:#169b62; color:#fff;">
    <h2 class="fw-bold mb-3">Ready to Defend Local Prosperity?</h2>
    <p class="mb-4">Take action to protect your culture, your money, and your community. Join today and invest in Ireland’s future.</p>
    <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg" style="border-color:#fff;">Create Your Account</a>
</div>

<div class="footer-irish">
    Made with <span style="color:#ff883e;">&#x2764;</span> in Ireland
    <span class="irish-flag"></span>
</div>
@endsection
