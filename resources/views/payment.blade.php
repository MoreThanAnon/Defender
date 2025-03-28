<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Form</title>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <h2>Pay $10.00</h2>

    <form id="payment-form">
        <div id="card-element">
            <!-- A Stripe Element will be inserted here. -->
        </div>

        <!-- Used to display form errors. -->
        <div id="card-errors" role="alert"></div>

        <button type="submit">Pay Now</button>
    </form>

    <script>
        var stripe = Stripe('your-publishable-key-here'); // Your publishable key from Stripe
        var elements = stripe.elements();
        var card = elements.create('card');
        card.mount('#card-element');

        // Handle form submission
        var form = document.getElementById('payment-form');
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            stripe.confirmCardPayment(clientSecret, {
                payment_method: {
                    card: card,
                    billing_details: {
                        name: 'Customer Name'
                    }
                }
            }).then(function(result) {
                if (result.error) {
                    // Show error to your customer
                    document.getElementById('card-errors').textContent = result.error.message;
                } else {
                    // The payment has been processed!
                    alert("Payment Successful!");
                    // Optionally, send a confirmation email or update the database
                }
            });
        });
    </script>
</body>
</html>