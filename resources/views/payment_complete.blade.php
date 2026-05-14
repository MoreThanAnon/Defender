@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center p-5">
                    <h1 class="h3 mb-3">Payment complete</h1>
                    <p class="text-muted mb-4">Your payment was processed successfully.</p>
                    <a href="{{ route('home') }}" class="btn btn-primary">Return home</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
