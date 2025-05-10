<!DOCTYPE html>
@extends('layouts.app')
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deposit Funds</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

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
            <!-- Deposit Amount Form -->
            <div id="depositAmountForm">
                <h2 class="mb-4 text-center">Enter Deposit Amount</h2>
                <form id="amountForm">
                    <div class="mb-3">
                        <label class="form-label">Deposit Amount (€)</label>
                        <input type="number" id="depositAmount" name="depositAmount" class="form-control" required placeholder="Enter amount" min="1" step="0.01">
                    </div>
                    <button type="button" id="nextButton" class="btn btn-primary w-100">Next</button>
                </form>
            </div>

            <!-- Payment Method Form -->
            <div id="paymentMethodForm" style="display: none;">
                <h2 class="mb-4 text-center">Add a Payment Method</h2>
                <form id="payment-form" action="{{ route('stripe.storePaymentMethod') }}" method="POST">
                    @csrf

                    <div class="alert alert-info text-center">
                        <p>You are depositing: <strong id="displayDepositAmount"></strong></p>
                        <p>Stripe Processing Fee (2.9% + €0.30): <strong id="stripeFee"></strong></p>
                        <p>Our Fee (2.5%): <strong id="ourFee"></strong></p>
                        <p>Total Amount Charged: <strong id="totalAmount"></strong></p>
                    </div>

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
                            <div class="invalid-feedback" id="state-error">Optional</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Eircode</label>
                            <input type="text" id="postal_code" class="form-control" placeholder="e.g. D02 X285" maxlength="10">
                            <div class="invalid-feedback" id="postal_code-error">Please enter a valid Eircode (e.g. D02 X285).</div>
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
                    <input type="hidden" name="totalAmount" id="hiddenTotalAmount">
                    <button id="submit-button" class="btn btn-primary w-100" type="submit">Save Card</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Stripe Setup
        const stripe = Stripe('pk_test_51R5TisG2co1PzDgDpnL9xWQ7nlNvBhGQYOGEZR1SaRhiwBtiwpsaRspvDHeQoYzvPYJoGMjjKKHYw8qvukTIEst000xcRqVE4l');
        const elements = stripe.elements();
        const card = elements.create('card', {
            hidePostalCode: true
        });
        card.mount('#card-element');
    
        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('submit-button');
        const cardErrors = document.getElementById('card-errors');
    
        // Step Form Controls
        const nextButton = document.getElementById('nextButton');
        const depositAmountForm = document.getElementById('depositAmountForm');
        const paymentMethodForm = document.getElementById('paymentMethodForm');
        const depositAmountInput = document.getElementById('depositAmount');
        const hiddenTotalAmount = document.getElementById('hiddenTotalAmount');
    
        nextButton.addEventListener('click', function () {
            const depositAmount = parseFloat(depositAmountInput.value);
    
            if (!depositAmount || isNaN(depositAmount) || depositAmount <= 0) {
                alert('Please enter a valid deposit amount.');
                return;
            }
    
            const stripeFee = (depositAmount * 0.029 + 0.30).toFixed(2);
            const ourFee = (depositAmount * 0.025).toFixed(2);
            const totalAmount = (depositAmount + parseFloat(stripeFee) + parseFloat(ourFee)).toFixed(2);
    
            document.getElementById('displayDepositAmount').textContent = `€${depositAmount.toFixed(2)}`;
            document.getElementById('stripeFee').textContent = `€${stripeFee}`;
            document.getElementById('ourFee').textContent = `€${ourFee}`;
            document.getElementById('totalAmount').textContent = `€${totalAmount}`;
            hiddenTotalAmount.value = totalAmount;
    
            depositAmountForm.style.display = 'none';
            paymentMethodForm.style.display = 'block';
        });
    
        // Stripe Payment Form Submission
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            submitButton.disabled = true;
            cardErrors.textContent = '';
    
            const billingDetails = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                address: {
                    line1: document.getElementById('line1').value,
                    city: document.getElementById('city').value,
                    state: document.getElementById('state').value,
                    postal_code: document.getElementById('postal_code').value,
                    country: document.getElementById('country').value,
                },
            };
    
            const { paymentMethod, error: pmError } = await stripe.createPaymentMethod({
                type: 'card',
                card: card,
                billing_details: billingDetails,
            });
    
            if (pmError) {
                cardErrors.textContent = pmError.message;
                submitButton.disabled = false;
                return;
            }
    
            const totalAmount = parseFloat(hiddenTotalAmount.value);
            let clientSecret;
    
            try {
                const response = await fetch("{{ route('stripe.createPaymentIntent') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        payment_method: paymentMethod.id,
                        amount: totalAmount
                    }),
                });
    
                const result = await response.json();
                if (result.error) {
                    cardErrors.textContent = result.error;
                    submitButton.disabled = false;
                    return;
                }
    
                clientSecret = result.client_secret;
            } catch (fetchError) {
                cardErrors.textContent = "Server error while creating payment intent.";
                console.error(fetchError);
                submitButton.disabled = false;
                return;
            }
    
            const { error: confirmError, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
                payment_method: paymentMethod.id,
                return_url: "{{ route('payment.complete') }}",
            });
    
            if (confirmError) {
                cardErrors.textContent = confirmError.message || "A payment processing error occurred.";
                console.error("Stripe confirm error:", confirmError);
                submitButton.disabled = false;
            } else {
                if (paymentIntent.status === 'succeeded') {
                    window.location.href = "{{ route('payment.complete') }}";
                }
            }
        });
    });
    </script>

</body>
</html>
