<?
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return response()->json(data: ['error' => 'Invalid signature']);
        }

        // Handle the event
        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;

            // Extract user ID and amount from metadata
            $userId = $paymentIntent->metadata->user_id ?? null;
            $amount = $paymentIntent->amount_received / 100; // Convert cents to dollars

            if ($userId && $amount) {
                $user = User::find($userId);
                if ($user) {
                    $user->funds += $amount;
                    $user->save();
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
