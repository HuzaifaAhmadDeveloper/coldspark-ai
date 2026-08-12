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

            // Capture SNS's own MessageId before unwrapping — it's only present
            // on the outer envelope, and it's what makes a redelivered
            // notification (SNS retries on any non-2xx/timeout) detectable as
            // the same event rather than a new one. See recordEvent()'s
            // provider_event_id idempotency check.
            $providerEventId = is_string($event['MessageId'] ?? null) ? $event['MessageId'] : null;

            $event = $this->unwrapSnsEnvelope($event);
            if ($event === null) continue; // e.g. SNS SubscriptionConfirmation — nothing to record

            // Fall back to SES's own IDs if this wasn't an SNS-wrapped payload
            // (or for extra safety even when it was).
            $providerEventId ??= $event['bounce']['feedbackId']
                ?? $event['complaint']['feedbackId']
                ?? $event['mail']['messageId']
                ?? null;

            if ($this->processEvent($event, $providerEventId)) $handled++;
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

    private function processEvent(array $event, ?string $providerEventId = null): bool
    {
        $type = $this->extractEventType($event);
        if (!$type) return false;

        $email = $this->resolveCampaignEmail($event);
        if (!$email) {
            Log::warning('campaign-events webhook: could not resolve campaign email', ['event' => $event]);
            return false;
        }

        RecordTrackingEventJob::dispatch($email->tracking_token, $type, ['source' => 'webhook'], $providerEventId);
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
            str_contains($raw, 'deliver')    => 'delivered',  // SES notificationType "Delivery"
            str_contains($raw, 'received')   => 'replied',    // SES inbound receiving: notificationType "Received"
            str_contains($raw, 'reply')      => 'replied',    // Mailgun/SendGrid-shaped inbound payloads
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

        // Plus-addressed inbound replies: reply+{token}@{domain}. Mailgun/SendGrid
        // give a single "recipient"/"to" string; SES inbound receiving gives an
        // array of addresses at mail.destination (and duplicates it at
        // receipt.recipients) since a message can technically have multiple
        // recipients — check every shape.
        $candidates = array_filter([
            $event['recipient'] ?? null,
            $event['To'] ?? null,
            $event['to'] ?? null,
            ...(is_array($event['mail']['destination'] ?? null) ? $event['mail']['destination'] : []),
            ...(is_array($event['receipt']['recipients'] ?? null) ? $event['receipt']['recipients'] : []),
        ]);

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && preg_match('/reply\+([a-zA-Z0-9]+)@/', $candidate, $m)) {
                $email = CampaignEmail::where('tracking_token', $m[1])->first();
                if ($email) return $email;
            }
        }

        // Custom headers echoed back by the ESP (SES/Mailgun/SendGrid all support this).
        $campaignEmailId = $this->findCampaignEmailIdFromHeaders(
            $event['headers'] ?? $event['message-headers'] ?? $event['mail']['headers'] ?? null
        );
        if ($campaignEmailId) {
            $email = CampaignEmail::find($campaignEmailId);
            if ($email) return $email;
        }

        return null;
    }

    /**
     * Finds our X-Campaign-Email-Id custom header (set in
     * CampaignEmailMailable::headers()) in whatever shape the ESP echoes
     * headers back in. SES gives an array of {"name": ..., "value": ...}
     * objects; Mailgun/some others give [name, value] pairs. A naive
     * json_encode+regex blob match (the original implementation) doesn't
     * actually match either structured shape — it only worked for a flat
     * "Name: value" string nobody's webhook actually sends.
     */
    private function findCampaignEmailIdFromHeaders(mixed $headers): ?int
    {
        if (is_array($headers)) {
            foreach ($headers as $header) {
                if (!is_array($header)) continue;

                // SES: {"name": "X-Campaign-Email-Id", "value": "5"}
                if (isset($header['name'], $header['value']) && strcasecmp((string) $header['name'], 'X-Campaign-Email-Id') === 0) {
                    return (int) $header['value'];
                }

                // Mailgun-style: ["X-Campaign-Email-Id", "5"]
                if (isset($header[0], $header[1]) && strcasecmp((string) $header[0], 'X-Campaign-Email-Id') === 0) {
                    return (int) $header[1];
                }
            }
        }

        // Fallback for a flat string blob, e.g. "X-Campaign-Email-Id: 5\r\n..."
        $blob = is_string($headers) ? $headers : (is_array($headers) ? json_encode($headers) : '');
        if ($blob && preg_match('/X-Campaign-Email-Id["\s:]+(\d+)/i', $blob, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
