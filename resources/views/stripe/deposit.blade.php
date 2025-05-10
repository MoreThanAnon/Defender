@extends('layouts.app')
@section('content')
<!DOCTYPE html>
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
        #success-message {
            color: #28a745;
            font-size: 1rem;
            margin-top: 20px;
        }
        #error-message {
            color: #dc3545;
            font-size: 1rem;
            margin-top: 20px;
        }
        #fee-breakdown {
            font-size: 0.95rem;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container py-5">
    @include('components.navbar')
    @if(!$hasDefault)
        <script> window.location.href = "{{ route('card.add') }}"; </script>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div id="depositAmountForm">
                <h2 class="mb-4 text-center">Enter Deposit Amount</h2>
                <form id="amountForm">
                    <div class="mb-3">
                        <label class="form-label">Deposit Amount (€)</label>
                        <input type="number" id="depositAmount" name="depositAmount" class="form-control" required placeholder="Enter amount" min="1" step="0.01">
                    </div>

                    <div id="fee-breakdown" class="text-muted">
                        <p><strong>Site Fee (2.5%):</strong> €<span id="site-fee">0.00</span></p>
                        <p><strong>Estimated Stripe Fee (1.4% + €0.25):</strong> €<span id="stripe-fee">0.00</span></p>
                        <p><strong>Net Amount Credited:</strong> €<span id="net-amount">0.00</span></p>
                    </div>

                    <button type="button" id="submit-button" class="btn btn-primary w-100">Deposit</button>
                </form>
                <div id="success-message" class="text-center"></div>
                <div id="error-message" class="text-center"></div>
            </div>
        </div>
    </div>
</div>

<script>
    const stripe = Stripe("pk_test_51R5TisG2co1PzDgDpnL9xWQ7nlNvBhGQYOGEZR1SaRhiwBtiwpsaRspvDHeQoYzvPYJoGMjjKKHYw8qvukTIEst000xcRqVE4l");
    const defaultPaymentMethod = "{{ $defaultPaymentMethodId }}";

    document.addEventListener('DOMContentLoaded', function () {
        const depositInput = document.getElementById('depositAmount');
        const siteFeeEl = document.getElementById('site-fee');
        const stripeFeeEl = document.getElementById('stripe-fee');
        const netAmountEl = document.getElementById('net-amount');
        const submitButton = document.getElementById('submit-button');
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');

        function updateFeeBreakdown() {
            const amount = parseFloat(depositInput.value);
            if (isNaN(amount) || amount <= 0) {
                siteFeeEl.textContent = stripeFeeEl.textContent = netAmountEl.textContent = '0.00';
                return;
            }

            const siteFee = +(amount * 0.025).toFixed(2);
            const stripeFee = +((amount * 0.014) + 0.25).toFixed(2);
            const net = +(amount - siteFee - stripeFee).toFixed(2);

            siteFeeEl.textContent = siteFee.toFixed(2);
            stripeFeeEl.textContent = stripeFee.toFixed(2);
            netAmountEl.textContent = net.toFixed(2);
        }

        depositInput.addEventListener('input', updateFeeBreakdown);
        updateFeeBreakdown();

        submitButton.addEventListener('click', async function (e) {
            e.preventDefault();
            const amount = parseFloat(depositInput.value);
            successMessage.textContent = '';
            errorMessage.textContent = '';

            if (!amount || amount <= 0) {
                errorMessage.textContent = 'Please enter a valid deposit amount.';
                return;
            }

            try {
                const response = await fetch("{{ route('stripe.processDeposit') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        amount: amount,
                        payment_method: defaultPaymentMethod,
                    }),
                });

                const data = await response.json();

                if (data.error) {
                    errorMessage.textContent = data.message || 'Something went wrong.';
                    return;
                }

                if (data.requires_action) {
                    const result = await stripe.confirmCardPayment(data.client_secret);

                    if (result.error) {
                        errorMessage.textContent = 'Payment failed: ' + result.error.message;
                    } else if (result.paymentIntent.status === 'succeeded') {
                        successMessage.textContent = `Deposit successful! Your new balance is €${data.new_balance}.`;
                    }
                } else if (data.success) {
                    successMessage.textContent = `Deposit successful! Your new balance is €${data.new_balance}.`;
                }

            } catch (error) {
                console.error('Error:', error);
                errorMessage.textContent = 'An error occurred. Please try again.';
            }
        });
    });
</script>

</body>
</html>
@endsection
