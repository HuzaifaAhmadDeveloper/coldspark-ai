<?php

namespace App\Mail;

use App\Models\CampaignEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignEmailMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CampaignEmail $campaignEmail,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = [];
        $replyDomain = config('services.email_webhook.reply_domain');

        if ($this->campaignEmail->tracking_token && $replyDomain) {
            $replyTo[] = new Address('reply+' . $this->campaignEmail->tracking_token . '@' . $replyDomain);
        }

        return new Envelope(
            subject: $this->campaignEmail->subject ?? 'No Subject',
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtmlBody(),
        );
    }

    /**
     * Build the HTML body from the plain-text email body: escape, convert
     * newlines to <br>, rewrite links for click-tracking, and append the
     * open-tracking pixel. Both tracking endpoints key off tracking_token.
     */
    private function buildHtmlBody(): string
    {
        $body  = e($this->campaignEmail->body ?? '');
        $body  = nl2br($body);
        $token = $this->campaignEmail->tracking_token;

        if ($token) {
            $body = preg_replace_callback('#https?://[^\s<]+#i', function (array $m) use ($token) {
                $original = html_entity_decode($m[0]);
                $tracked  = route('campaign.track.click', ['token' => $token]) . '?url=' . urlencode($original);
                return '<a href="' . e($tracked) . '" style="color:#2563eb;">' . $m[0] . '</a>';
            }, $body);
        }

        $pixel = $token
            ? '<img src="' . e(route('campaign.track.open', ['token' => $token])) . '" width="1" height="1" style="display:none" alt="" />'
            : '';

        $footer = $token
            ? '<p style="margin-top:24px;padding-top:12px;border-top:1px solid #e5e7eb;font-size:11px;color:#9ca3af;">'
                . '<a href="' . e(route('campaign.unsubscribe', ['token' => $token])) . '" style="color:#9ca3af;">Unsubscribe</a>'
                . '</p>'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6;">
{$body}
{$footer}
{$pixel}
</body>
</html>
HTML;
    }

    /**
     * Production-quality headers: campaign correlation IDs for the inbound
     * webhook, and List-Unsubscribe / List-Unsubscribe-Post so Gmail/Yahoo/
     * Outlook show a native one-click unsubscribe control — required for
     * bulk senders since 2024, and one of the biggest single levers on
     * inbox-vs-spam placement. Message-ID/MIME/UTF-8 are handled by
     * Symfony Mailer automatically and don't need to be set here.
     */
    public function headers(): \Illuminate\Mail\Mailables\Headers
    {
        $token = $this->campaignEmail->tracking_token;
        $text  = [
            'X-Campaign-Id'       => (string) $this->campaignEmail->campaign_id,
            'X-Campaign-Email-Id' => (string) $this->campaignEmail->id,
        ];

        if ($token) {
            $unsubscribeUrl = route('campaign.unsubscribe', ['token' => $token]);
            $replyDomain    = config('services.email_webhook.reply_domain');

            $listUnsubscribe = $replyDomain
                ? "<mailto:unsubscribe+{$token}@{$replyDomain}>, <{$unsubscribeUrl}>"
                : "<{$unsubscribeUrl}>";

            $text['List-Unsubscribe']      = $listUnsubscribe;
            $text['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
        }

        return new \Illuminate\Mail\Mailables\Headers(text: $text);
    }
}
