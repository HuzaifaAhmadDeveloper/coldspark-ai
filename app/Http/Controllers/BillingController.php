<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    public function plans()
    {
        $user = Auth::user();
        return view('billing.plans', [
            'user'        => $user,
            'plan'        => $user->getPlanName(),
            'credits'     => $user->getCredits(),
            'onPro'       => $user->subscribedToPrice(env('STRIPE_PRO_PRICE'), 'default'),
            'onBusiness'  => $user->subscribedToPrice(env('STRIPE_BUSINESS_PRICE'), 'default'),
        ]);
    }

    public function checkout(Request $request)
    {
        $user     = Auth::user();
        $priceId  = $request->price_id;

        $allowedPrices = [
            env('STRIPE_PRO_PRICE'),
            env('STRIPE_BUSINESS_PRICE'),
        ];

        if (!in_array($priceId, $allowedPrices)) {
            abort(403, 'Invalid plan.');
        }

        return $user->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('billing.plans'),
            ]);
    }

    public function success(Request $request)
{
    $user = Auth::user();

    // Get price from session or detect from recent subscription
    $sessionId = $request->get('session_id');

    $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

    try {
        $session = $stripe->checkout->sessions->retrieve($sessionId);
        $priceId = $session->line_items->data[0]->price->id ?? null;

        // Reload session with line items
        $session = $stripe->checkout->sessions->retrieve(
            $sessionId,
            ['expand' => ['line_items']]
        );
        $priceId = $session->line_items->data[0]->price->id ?? null;

    } catch (\Exception $e) {
        $priceId = null;
    }

    if ($priceId === env('STRIPE_BUSINESS_PRICE')) {
        $plan    = 'Business';
        $credits = 500;
    } elseif ($priceId === env('STRIPE_PRO_PRICE')) {
        $plan    = 'Pro';
        $credits = 100;
    } else {
        $plan    = 'Pro';
        $credits = 100;
    }

    if ($user->credit) {
        $user->credit->update(['balance' => $credits]);
    } else {
        \App\Models\Credit::create([
            'user_id' => $user->id,
            'balance' => $credits
        ]);
    }

    return view('billing.success', compact('plan', 'credits'));
}

    public function cancel(Request $request)
    {
        $user = Auth::user();

        if ($user->subscribed('default')) {
            $user->subscription('default')->cancel();
        }

        return redirect()->route('billing.plans')
            ->with('message', 'Subscription cancelled. Access continues until end of billing period.');
    }

    public function portal(Request $request)
    {
        return Auth::user()->redirectToBillingPortal(route('billing.plans'));
    }
}