
<!DOCTYPE html>
@extends('layouts.app')
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Payment Method</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .StripeElement {
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            background-color: white;
        }

        .StripeElement--focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.25rem rgba(0,123,255,.25);
        }

        #card-errors {
            color: #dc3545;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    
<div class="container py-5">
    @include('components.navbar')

    <div class="row justify-content-center">
        <div class="col-md-8">

            <h2 class="mb-4 text-center">Add a Payment Method</h2>

            <form id="payment-form" action="{{ route('stripe.storePaymentMethod') }}" method="POST">
                @csrf

                <!-- Billing Details -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" id="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" id="email" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Address Line 1</label>
                    <input type="text" id="line1" class="form-control" required>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" id="city" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State</label>
                        <input type="text" id="state" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Postal Code</label>
                        <input type="text" id="postal_code" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Country</label>
                    <input type="text" id="country" class="form-control" required>
                </div>

                <!-- Card Element -->
                <div class="mb-3">
                    <label class="form-label">Card Details</label>
                    <div id="card-element" class="form-control StripeElement"></div>
                    <div id="card-errors" class="text-danger mt-2"></div>
                </div>

                <input type="hidden" name="payment_method" id="payment-method">
                <button id="submit-button" class="btn btn-primary w-100" type="submit">Save Card</button>
            </form>

        </div>
    </div>
</div>

<script>
    const stripe = Stripe("{{ env('STRIPE_KEY') }}");
    const elements = stripe.elements();
    const cardElement = elements.create('card');
    cardElement.mount('#card-element');

    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const cardErrors = document.getElementById('card-errors');

    // Real-time validation error handling
    cardElement.on('change', function(event) {
        cardErrors.textContent = event.error ? event.error.message : '';
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        submitButton.disabled = true;

        // Collect billing details
        const billingDetails = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            address: {
                line1: document.getElementById('line1').value,
                city: document.getElementById('city').value,
                state: document.getElementById('state').value,
                postal_code: document.getElementById('postal_code').value,
                country: document.getElementById('country').value
            }
        };

        const { setupIntent, error } = await stripe.confirmCardSetup(
            "{{ $intent->client_secret }}",
            {
                payment_method: {
                    card: cardElement,
                    billing_details: billingDetails
                }
            }
        );

        if (error) {
            cardErrors.textContent = error.message;
            submitButton.disabled = false;
        } else {
            document.getElementById('payment-method').value = setupIntent.payment_method;
            form.submit();
        }
    });
</script>
</body>
</html>
