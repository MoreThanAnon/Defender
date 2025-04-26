<!DOCTYPE html>
@extends('layouts.app')
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deposit Funds</title>
    
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

                    <!-- Display the deposit amount -->
                    <div class="alert alert-info text-center">
                        <p>You are depositing: <strong id="displayDepositAmount"></strong></p>
                        <p>Stripe Processing Fee (2.9% + €0.30): <strong id="stripeFee"></strong></p>
                        <p>Our Fee (2.5%): <strong id="ourFee"></strong></p>
                        <p>Total Amount Charged: <strong id="totalAmount"></strong></p>
                    </div>

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
                    <input type="hidden" name="totalAmount" id="hiddenTotalAmount">
                    <button id="submit-button" class="btn btn-primary w-100" type="submit">Save Card</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nextButton = document.getElementById('nextButton');
        const depositAmountForm = document.getElementById('depositAmountForm');
        const paymentMethodForm = document.getElementById('paymentMethodForm');
        const displayDepositAmount = document.getElementById('displayDepositAmount');
        const stripeFeeElement = document.getElementById('stripeFee');
        const ourFeeElement = document.getElementById('ourFee');
        const totalAmountElement = document.getElementById('totalAmount');
        const hiddenTotalAmount = document.getElementById('hiddenTotalAmount');
        const depositAmountInput = document.getElementById('depositAmount');

        nextButton.addEventListener('click', function () {
            const depositAmount = parseFloat(depositAmountInput.value);

            // Validate the deposit amount
            if (!depositAmount || isNaN(depositAmount) || depositAmount <= 0) {
                alert('Please enter a valid deposit amount.');
                return;
            }

            // Calculate fees
            const stripeFee = (depositAmount * 0.029 + 0.30).toFixed(2); // Stripe fee: 2.9% + €0.30
            const ourFee = (depositAmount * 0.025).toFixed(2); // Our fee: 2.5%
            const totalAmount = (depositAmount + parseFloat(stripeFee) + parseFloat(ourFee)).toFixed(2);

            // Update the UI
            displayDepositAmount.textContent = `€${depositAmount.toFixed(2)}`;
            stripeFeeElement.textContent = `€${stripeFee}`;
            ourFeeElement.textContent = `€${ourFee}`;
            totalAmountElement.textContent = `€${totalAmount}`;
            hiddenTotalAmount.value = totalAmount;

            // Show the payment method form and hide the deposit amount form
            depositAmountForm.style.display = 'none';
            paymentMethodForm.style.display = 'block';
        });
    });
</script>
</body>
</html>
