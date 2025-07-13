@extends('layouts.app')

@section('content')
<div class="hero-section text-center text-white d-flex flex-column justify-content-center align-items-center">
    <div class="container py-5">
        <h1 class="display-4 fw-bold">Invest in Real Estate With Ease</h1>
        <p class="lead mb-4">Join other investors and build a portfolio of professionally managed properties.</p>
        <div>
            <a href="{{ route('register') }}" class="btn btn-primary me-2">Get Started</a>
            <a href="{{ route('login') }}" class="btn btn-outline-light">Login</a>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row text-center mb-5">
        <h2 class="fw-bold">Why Choose Our Platform</h2>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="feature-card p-4 h-100">
                <h5 class="fw-bold mb-2">Diversified Portfolio</h5>
                <p class="mb-0">Access a mix of residential and commercial opportunities selected by our team.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card p-4 h-100">
                <h5 class="fw-bold mb-2">Automated Investing</h5>
                <p class="mb-0">Set your preferences and let our system allocate funds across multiple projects.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card p-4 h-100">
                <h5 class="fw-bold mb-2">Transparent Reporting</h5>
                <p class="mb-0">Monitor performance with quarterly updates and detailed dashboards.</p>
            </div>
        </div>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container">
        <div class="row text-center mb-4">
            <h2 class="fw-bold">How It Works</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div class="step-icon mb-3">1</div>
                <h5 class="fw-bold">Create an Account</h5>
                <p class="mb-0">Sign up to explore available investments and manage your portfolio.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="step-icon mb-3">2</div>
                <h5 class="fw-bold">Invest in Projects</h5>
                <p class="mb-0">Choose from curated opportunities or enable auto-invest to diversify automatically.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="step-icon mb-3">3</div>
                <h5 class="fw-bold">Watch Your Earnings</h5>
                <p class="mb-0">Track returns over time and reinvest distributions to grow your account.</p>
            </div>
        </div>
    </div>
</div>

<div class="text-center py-5">
    <h2 class="fw-bold mb-3">Ready to Begin?</h2>
    <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Create Your Account</a>
</div>
@endsection
