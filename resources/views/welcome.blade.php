@extends('layouts.app')

@section('content')
<div class="row justify-content-center"><div class="col-lg-9">
    <div class="py-4">
        <h1 class="display-6 fw-bold" style="color: var(--bc-ink)">Run your business from one screen.</h1>
        <p class="lead">Operations and transparency for small shared businesses and property teams: budgets, approvals, bank reconciliation, tasks, records and a monthly report.</p>
        <p>
            @auth
                <a class="btn btn-primary btn-lg" href="{{ route('bc.index') }}">Go to my businesses</a>
            @else
                <a class="btn btn-primary btn-lg" href="{{ route('register') }}">Get started</a>
                <a class="btn btn-outline-primary btn-lg" href="{{ route('login') }}">Sign in</a>
            @endauth
        </p>
    </div>
    <div class="row g-3">
        <div class="col-md-4"><div class="card h-100"><div class="card-body">
            <h2 class="h5">Your company</h2>
            <p class="mb-0">Each house, business or piece of land is its own record, owned and run by you. You can export everything and leave at any time.</p>
        </div></div></div>
        <div class="col-md-4"><div class="card h-100"><div class="card-body">
            <h2 class="h5">Your bank</h2>
            <p class="mb-0">Upload statements from your own bank. {{ config('app.name') }} never holds, receives or moves your money, and never asks for banking passwords.</p>
        </div></div></div>
        <div class="col-md-4"><div class="card h-100"><div class="card-body">
            <h2 class="h5">Your decisions</h2>
            <p class="mb-0">Propose, discuss, vote and record who approved what. Every step is kept in an audit history your accountant can read.</p>
        </div></div></div>
    </div>
</div></div>
@endsection
