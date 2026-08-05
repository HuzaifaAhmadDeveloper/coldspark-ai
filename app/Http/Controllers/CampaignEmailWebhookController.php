<?php

namespace App\Http\Controllers;

use App\Jobs\RecordTrackingEventJob;
use App\Models\CampaignEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Generic inbound endpoint for ESP events that can't be observed client-side:
 * bounces/complaints (SES SNS, Mailgun, SendGrid, Postmark webhooks) and
 * inbound replies (Mailgun Routes / SendGrid Inbound Parse posting to a
 * reply+{token}@{MAIL_REPLY_DOMAIN} address — see CampaignEmailMailable).
 *
 * Point your ESP's webhook/inbound-route at:
 *   POST /webhooks/campaign-events?key={EMAIL_WEBHOOK_SECRET}
 * This requires the reply/bounce webhook to be configured in the ESP's own
 * dashboard (and, for inbound replies, MX/DNS for MAIL_REPLY_DOMAIN) —
 * that provider-side setup is outside this codebase.
 */
class CampaignEmailWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secret = config('services.email_webhook.secret');
        if (!$secret || $request->query('key') !== $secret) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        $payload = $request->all();
        $events  = array_is_list($payload) ? $payload : [$payload];

        $handled = 0;
        foreach ($events as $event) {
            if (!is_array($event)) continue;
            if ($this->processEvent($event)) $handled++;
        }

        Log::info('campaign-events webhook received', ['count' => count($events), 'handled' => $handled]);

        return response()->json(['received' => count($events), 'handled' => $handled]);
    }

    private function processEvent(array $event): bool
    {
        $type = $this->extractEventType($event);
        if (!$type) return false;

        $email = $this->resolveCampaignEmail($event);
        if (!$email) {
            Log::warning('campaign-events webhook: could not resolve campaign email', ['event' => $event]);
            return false;
        }

        RecordTrackingEventJob::dispatch($email->tracking_token, $type, ['source' => 'webhook']);
        return true;
    }

    private function extractEventType(array $event): ?string
    {
        $raw = strtolower((string) (
            $event['event'] ?? $event['notificationType'] ?? $event['Type'] ?? $event['type'] ?? ''
        ));

        return match (true) {
            str_contains($raw, 'bounce')     => 'bounced',
            str_contains($raw, 'complaint')  => 'bounced',
            str_contains($raw, 'reply')      => 'replied',
            str_contains($raw, 'inbound')    => 'replied',
            default                          => null,
        };
    }

    private function resolveCampaignEmail(array $event): ?CampaignEmail
    {
        // Direct token/id references, if the caller (or our own outbound
        // custom headers echoed back by the ESP) provided one.
        foreach (['tracking_token', 'token'] as $key) {
            if (!empty($event[$key])) {
                $email = CampaignEmail::where('tracking_token', $event[$key])->first();
                if ($email) return $email;
            }
        }
        if (!empty($event['campaign_email_id'])) {
            $email = CampaignEmail::find($event['campaign_email_id']);
            if ($email) return $email;
        }

        // Plus-addressed inbound replies: reply+{token}@{domain}
        $recipient = $event['recipient'] ?? $event['To'] ?? $event['to'] ?? '';
        if (is_string($recipient) && preg_match('/reply\+([a-zA-Z0-9]+)@/', $recipient, $m)) {
            return CampaignEmail::where('tracking_token', $m[1])->first();
        }

        // Custom headers echoed back by the ESP (SES/Mailgun/SendGrid all support this).
        $headerBlob = json_encode($event['headers'] ?? $event['message-headers'] ?? $event['mail']['headers'] ?? []);
        if ($headerBlob && preg_match('/X-Campaign-Email-Id["\s:,]+(\d+)/i', $headerBlob, $m)) {
            return CampaignEmail::find((int) $m[1]);
        }

        return null;
    }
}
