@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold">Welcome to Defender</h1>
        <p class="lead">Invest in premium hotel properties together. Track progress and stay informed with real-time updates.</p>
        <div class="mt-4">
            <a href="{{ route('login') }}" class="btn btn-primary me-2">Login</a>
            <a href="{{ route('register') }}" class="btn btn-outline-primary">Register</a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2>What We Do</h2>
            <p>Defender allows communities to collectively fund hotel acquisitions and monitor their performance. Members can bid on opportunities, follow updates, and participate in proposal voting.</p>
            <p class="small text-muted">All investments are subject to risk. Nothing on this site constitutes financial advice. Please review our terms and conditions before participating.</p>
        </div>
    </div>
</div>
@endsection
