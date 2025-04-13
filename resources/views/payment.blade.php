@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <script src="https://js.stripe.com/v3/"></script>

    <h1>Deposit Funds</h1>

    <form id="payment-form" action="{{ route('payment.handle') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="amount">Amount to Deposit</label>
            <input type="number" class="form-control" id="amount" name="amount" required>
        </div>

        <div class="form-group mt-3">
            <label for="card-element">Card Details</label>
            <div id="card-element" class="form-control p-2"></div>
            <div id="card-errors" class="text-danger mt-2"></div>
        </div>

        <input type="hidden" name="stripeToken" id="stripeToken">

        <button type="submit" class="btn btn-primary mt-3">Deposit</button>
    </form>
</div>
@endsection

@section('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe("pk_test_..."); // Replace with your public Stripe key
    const elements = stripe.elements();
    const card = elements.create("card");
    card.mount("#card-element");

    const form = document.getElementById("payment-form");
    const errorDiv = document.getElementById("card-errors");

    form.addEventListener("submit", async function(event) {
        event.preventDefault();

        const {token, error} = await stripe.createToken(card);

        if (error) {
            errorDiv.textContent = error.message;
        } else {
            document.getElementById("stripeToken").value = token.id;
            form.submit();
        }
    });
</script>
@endsection