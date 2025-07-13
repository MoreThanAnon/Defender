@extends('layouts.app')
@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <h3 class="text-center text-danger mb-4">🏦 Withdraw Funds</h3>

                    <form id="withdrawForm">
                        <div class="mb-3">
                            <label for="withdrawAmount" class="form-label fw-semibold">Withdraw Amount (€)</label>
                            <input type="number" id="withdrawAmount" name="withdrawAmount"
                                   class="form-control form-control-lg"
                                   required placeholder="Enter amount" min="1" step="0.01">
                        </div>

                        <button type="button" id="withdraw-button" class="btn btn-danger w-100 py-2">
                            Withdraw
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
    document.getElementById('withdraw-button').addEventListener('click', async function () {
        const amount = parseFloat(document.getElementById('withdrawAmount').value);
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');

        successMessage.classList.add('d-none');
        errorMessage.classList.add('d-none');

        if (!amount || amount <= 0) {
            errorMessage.textContent = 'Please enter a valid withdrawal amount.';
            errorMessage.classList.remove('d-none');
            return;
        }

        try {
            const response = await fetch("{{ route('stripe.withdraw') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({ amount: amount })
            });

            const data = await response.json();

            if (data.success) {
                successMessage.textContent = `Withdrawal successful! Your new balance is €${data.new_balance}.`;
                successMessage.classList.remove('d-none');
            } else {
                errorMessage.textContent = data.message || 'Something went wrong.';
                errorMessage.classList.remove('d-none');
            }
        } catch (error) {
            console.error('Error:', error);
            errorMessage.textContent = 'An error occurred. Please try again.';
            errorMessage.classList.remove('d-none');
        }
    });
</script>

@endsection
