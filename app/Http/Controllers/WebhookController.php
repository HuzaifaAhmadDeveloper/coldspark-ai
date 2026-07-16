<?php
namespace App\Http\Controllers;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhook;

class WebhookController extends CashierWebhook
{
    public function handleCheckoutSessionCompleted(array $payload): \Symfony\Component\HttpFoundation\Response
    {
        $session = $payload['data']['object'];
        $customerId = $session['customer'] ?? null;

        if (!$customerId) return $this->successMethod();

        $user = User::where('stripe_id', $customerId)->first();
        if (!$user) return $this->successMethod();

        $priceId = $session['line_items']['data'][0]['price']['id']
            ?? $this->getPriceFromSession($session['id']);

        $credits = match($priceId) {
            env('STRIPE_BUSINESS_PRICE') => 500,
            env('STRIPE_PRO_PRICE')      => 100,
            default                       => 10,
        };

        Credit::updateOrCreate(
            ['user_id' => $user->id],
            ['balance' => $credits]
        );

        return $this->successMethod();
    }

    private function getPriceFromSession(string $sessionId): ?string
    {
        try {
            $stripe  = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            $session = $stripe->checkout->sessions->retrieve(
                $sessionId,
                ['expand' => ['line_items']]
            );
            return $session->line_items->data[0]->price->id ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}