<?php

namespace App\Http\Controllers;

use App\Jobs\RecordTrackingEventJob;
use App\Models\CampaignEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Generic inbound endpoint for ESP events that can't be observed client-side:
 * deliveries/bounces/complaints (SES via SNS, Mailgun, SendGrid, Postmark
 * webhooks) and inbound replies (Mailgun Routes / SendGrid Inbound Parse
 * posting to a reply+{token}@{MAIL_REPLY_DOMAIN} address — see
 * CampaignEmailMailable).
 *
 * Point your ESP's webhook/inbound-route at:
 *   POST /webhooks/campaign-events?key={EMAIL_WEBHOOK_SECRET}
 * For SES: create an SNS topic, subscribe this URL to it, and add it as an
 * event destination on the SES Configuration Set referenced by
 * SES_CONFIGURATION_SET (see AWS setup docs). This requires the
 * reply/bounce webhook to be configured in the ESP's own dashboard (and,
 * for inbound replies, MX/DNS for MAIL_REPLY_DOMAIN) — that provider-side
 * setup is outside this codebase.
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

            $event = $this->unwrapSnsEnvelope($event);
            if ($event === null) continue; // e.g. SNS SubscriptionConfirmation — nothing to record

            if ($this->processEvent($event)) $handled++;
        }

        Log::info('campaign-events webhook received', ['count' => count($events), 'handled' => $handled]);

        return response()->json(['received' => count($events), 'handled' => $handled]);
    }

    /**
     * SES delivers Bounce/Complaint/Delivery events via SNS, which wraps the
     * real payload in an envelope — the actual SES notification (notificationType,
     * mail.headers, bounce/complaint/delivery) is a JSON-encoded STRING in the
     * "Message" field, not top-level. Unwrap it so extractEventType()/
     * resolveCampaignEmail() see the real fields. Non-SNS payloads (Mailgun,
     * SendGrid, Postmark) have no "Type" envelope and pass through unchanged.
     */
    private function unwrapSnsEnvelope(array $event): ?array
    {
        $type = $event['Type'] ?? null;
        if ($type === null) {
            return $event;
        }

        if ($type === 'SubscriptionConfirmation' || $type === 'UnsubscribeConfirmation') {
            // AWS requires visiting SubscribeURL once to activate the SNS topic
            // subscription. Logged rather than auto-fetched: this is a public
            // endpoint, and blindly GET-ing an attacker-supplied URL from the
            // request body would be an SSRF risk even behind the shared secret.
            Log::warning("campaign-events webhook: SNS {$type} — visit SubscribeURL once to activate", [
                'topic_arn'     => $event['TopicArn'] ?? null,
                'subscribe_url' => $event['SubscribeURL'] ?? null,
            ]);
            return null;
        }

        if ($type === 'Notification' && isset($event['Message']) && is_string($event['Message'])) {
            $inner = json_decode($event['Message'], true);
            return is_array($inner) ? $inner : $event;
        }

        return $event;
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
            str_contains($raw, 'bounce')     => $this->isHardBounce($event) ? 'bounced' : 'soft_bounced',
            str_contains($raw, 'complaint')  => 'complaint',
            str_contains($raw, 'deliver')    => 'delivered', // SES notificationType "Delivery"
            str_contains($raw, 'reply')      => 'replied',
            str_contains($raw, 'inbound')    => 'replied',
            default                          => null,
        };
    }

    /**
     * SES: bounce.bounceType is "Permanent" (bad address — hard) vs "Transient"/
     * "Undetermined" (mailbox full, greylisted, etc — soft, worth retrying).
     * Mailgun/SendGrid use their own field names for the same distinction.
     * Default to "hard" when a provider gives no sub-type at all — safer to
     * suppress a possibly-recoverable address than to keep hammering a dead one.
     */
    private function isHardBounce(array $event): bool
    {
        $sesType = strtolower((string) ($event['bounce']['bounceType'] ?? ''));
        if ($sesType !== '') {
            return $sesType === 'permanent';
        }

        $severity = strtolower((string) ($event['severity'] ?? $event['bounce_classification'] ?? ''));
        if ($severity !== '') {
            return !str_contains($severity, 'temp') && !str_contains($severity, 'transient') && !str_contains($severity, 'soft');
        }

        return true;
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
