@extends('layouts.app')
@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://js.stripe.com/v3/"></script>

<div class="container py-5">
    @include('components.navbar')

    @if(!$hasDefault)
        <script> window.location.href = "{{ route('card.add') }}"; </script>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <h3 class="text-center text-primary mb-4">💳 Add Funds to Your Account</h3>

                    <form id="amountForm">
                        <div class="mb-3">
                            <label for="depositAmount" class="form-label fw-semibold">Deposit Amount (€)</label>
                            <input type="number" id="depositAmount" name="depositAmount"
                                   class="form-control form-control-lg"
                                   required placeholder="Enter amount" min="1" step="0.01">
                        </div>

                        <div id="fee-breakdown" class="bg-light p-3 rounded mb-3 small">
                            <p class="mb-1"><strong>Site Fee (2.5%):</strong> €<span id="site-fee">0.00</span></p>
                            <p class="mb-1"><strong>Stripe Fee (1.4% + €0.25):</strong> €<span id="stripe-fee">0.00</span></p>
                            <hr>
                            <p class="mb-0"><strong>Net Amount Credited:</strong> €<span id="net-amount" class="text-success">0.00</span></p>
                        </div>

                        <button type="button" id="submit-button" class="btn btn-primary w-100 py-2">
                            Deposit
                        </button>
                    </form>

                    <div id="success-message" class="alert alert-success text-center mt-3 d-none"></div>
                    <div id="error-message" class="alert alert-danger text-center mt-3 d-none"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const stripe = Stripe("{{ env('STRIPE_KEY') }}");
    const defaultPaymentMethod = "{{ $defaultPaymentMethodId }}";

    document.addEventListener('DOMContentLoaded', function () {
        const depositInput = document.getElementById('depositAmount');
        const siteFeeEl = document.getElementById('site-fee');
        const stripeFeeEl = document.getElementById('stripe-fee');
        const netAmountEl = document.getElementById('net-amount');
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');
        const submitButton = document.getElementById('submit-button');

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
            successMessage.classList.add('d-none');
            errorMessage.classList.add('d-none');

            if (!amount || amount <= 0) {
                errorMessage.textContent = 'Please enter a valid deposit amount.';
                errorMessage.classList.remove('d-none');
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
                    errorMessage.classList.remove('d-none');
                    return;
                }

                if (data.requires_action) {
                    const result = await stripe.confirmCardPayment(data.client_secret);
                    if (result.error) {
                        errorMessage.textContent = 'Payment failed: ' + result.error.message;
                        errorMessage.classList.remove('d-none');
                    } else if (result.paymentIntent.status === 'succeeded') {
                        successMessage.textContent = `Deposit successful! Your new balance is €${data.new_balance}.`;
                        successMessage.classList.remove('d-none');
                    }
                } else if (data.success) {
                    successMessage.textContent = `Deposit successful! Your new balance is €${data.new_balance}.`;
                    successMessage.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Error:', error);
                errorMessage.textContent = 'An error occurred. Please try again.';
                errorMessage.classList.remove('d-none');
            }
        });
    });
</script>

@endsection
