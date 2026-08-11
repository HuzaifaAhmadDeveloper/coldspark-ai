<?php

namespace App\Http\Controllers;

use App\Services\CampaignService;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    /**
     * GET shows a confirmation page (a bare link must never unsubscribe
     * someone — link scanners/prefetchers would trigger it accidentally).
     * POST performs the actual unsubscribe and is what mailbox providers'
     * native "Unsubscribe" button calls per RFC 8058 (List-Unsubscribe-Post),
     * as well as the confirm button on the GET page below.
     */
    public function handle(string $token, Request $request, CampaignService $service)
    {
        $email = $service->findByTrackingToken($token);

        if ($request->isMethod('post')) {
            if ($email) {
                $service->markUnsubscribed($email->prospect);
            }

            // One-click compliance (RFC 8058): respond immediately, no redirect/page.
            if ($request->wantsJson() || $request->header('List-Unsubscribe-Post')) {
                return response()->noContent();
            }

            return view('unsubscribe', ['done' => true]);
        }

        return view('unsubscribe', [
            'done'     => false,
            'token'    => $token,
            'found'    => (bool) $email,
            'prospect' => $email?->prospect,
        ]);
    }
}
