@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h1>Deposit Funds</h1>
    <form action="{{ route('payment.handle') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="amount">Amount to Deposit</label>
            <input type="number" class="form-control" id="amount" name="amount" required>
        </div>
        <button type="submit" class="btn btn-primary">Deposit</button>
    </form>
</div>
@endsection
