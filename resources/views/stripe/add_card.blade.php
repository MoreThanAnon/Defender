@extends('layouts.app')

@section('content')
<div class="container py-5">
    @include('components.navbar')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4 text-center">Payment Method</h2>

            @if (auth()->user()->hasDefaultPaymentMethod())
                <!-- Card Summary -->
                <div id="card-summary">
                    <div class="alert alert-info">
                        <p><strong>Card Brand:</strong> <span id="card-brand"></span></p>
                        <p><strong>Last 4 Digits:</strong> <span id="card-last4"></span></p>
                        <p><strong>Expiry:</strong> <span id="card-expiry"></span></p>
                    </div>
                    <form id="delete-card-form" method="POST" action="{{ route('card.delete') }}">
                        @csrf
                        <input type="hidden" name="card_type" id="card-type" value="card">
                        <button type="submit" class="btn btn-danger w-100">Delete Card</button>
                    </form>
                </div>
            @else
                <!-- Add Card Form -->
                <form id="setup-form" method="POST" action="{{ route('card.save') }}">
                    @csrf

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" id="name" class="form-control" required>
                            <div class="invalid-feedback" id="name-error">Full name is required.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" id="email" class="form-control" required>
                            <div class="invalid-feedback" id="email-error">Enter a valid email address.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address Line 1</label>
                        <input type="text" id="line1" class="form-control" required>
                        <div class="invalid-feedback" id="line1-error">Address is required.</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" id="city" class="form-control" required>
                            <div class="invalid-feedback" id="city-error">City is required.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" id="state" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Eircode</label>
                            <input type="text" id="postal_code" class="form-control" placeholder="e.g. D02 X285" maxlength="10">
                            <div class="invalid-feedback" id="postal_code-error">Enter a valid Eircode.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Country</label>
                        <select id="country" class="form-control" required>
                            <option value="">Select Country</option>
                            <option value="IE" selected>Ireland</option>
                            <option value="GB">United Kingdom</option>
                            <option value="US">United States</option>
                            <option value="FR">France</option>
                            <option value="DE">Germany</option>
                        </select>
                        <div class="invalid-feedback" id="country-error">Please select a country.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Card Details</label>
                        <div id="card-element" class="form-control StripeElement"></div>
                        <div id="card-errors" class="text-danger mt-2"></div>
                    </div>

                    <input type="hidden" name="payment_method" id="payment-method">
                    <button type="submit" class="btn btn-primary w-100" id="submit-button">Save Card</button>
                </form>
            @endif
        </div>
    </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const stripe = Stripe("pk_test_51R5TisG2co1PzDgDpnL9xWQ7nlNvBhGQYOGEZR1SaRhiwBtiwpsaRspvDHeQoYzvPYJoGMjjKKHYw8qvukTIEst000xcRqVE4l");

    @if (auth()->user()->hasDefaultPaymentMethod())
        const response = await fetch("{{ route('card.details') }}", {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        const data = await response.json();
        if (data.success) {
            document.getElementById('card-brand').textContent = data.brand;
            document.getElementById('card-last4').textContent = data.last4;
            document.getElementById('card-expiry').textContent = `${data.exp_month}/${data.exp_year}`;
        } else {
            alert('Failed to fetch card details.');
        }
    @else
        const elements = stripe.elements();
        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    '::placeholder': { color: '#aab7c4' }
                },
                invalid: { color: '#fa755a' }
            }
        });
        cardElement.mount('#card-element');

        const form = document.getElementById('setup-form');
        const submitButton = document.getElementById('submit-button');

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            submitButton.disabled = true;
            document.getElementById('card-errors').textContent = '';

            try {
                const response = await fetch("{{ route('stripe.createSetupIntent') }}", {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                });

                const data = await response.json();
                if (!data.client_secret) throw new Error('Client secret not found.');

                const { setupIntent, error } = await stripe.confirmCardSetup(data.client_secret, {
                    payment_method: {
                        card: cardElement,
                        billing_details: {
                            name: document.getElementById('name').value,
                            email: document.getElementById('email').value,
                            address: {
                                line1: document.getElementById('line1').value,
                                city: document.getElementById('city').value,
                                state: document.getElementById('state').value,
                                postal_code: document.getElementById('postal_code').value,
                                country: document.getElementById('country').value
                            }
                        }
                    }
                });

                if (error) {
                    document.getElementById('card-errors').textContent = error.message;
                    submitButton.disabled = false;
                } else if (setupIntent.status === 'succeeded') {
                    document.getElementById('payment-method').value = setupIntent.payment_method;
                    form.submit();
                } else if (setupIntent.status === 'requires_action') {
                    const result = await stripe.handleCardSetup(data.client_secret);
                    if (result.error) {
                        document.getElementById('card-errors').textContent = result.error.message;
                        submitButton.disabled = false;
                    } else {
                        document.getElementById('payment-method').value = result.setupIntent.payment_method;
                        form.submit();
                    }
                } else {
                    document.getElementById('card-errors').textContent = 'Unexpected status: ' + setupIntent.status;
                    submitButton.disabled = false;
                }

            } catch (error) {
                console.error('Error:', error);
                document.getElementById('card-errors').textContent = error.message;
                submitButton.disabled = false;
            }
        });
    @endif
});
</script>
@endsection
