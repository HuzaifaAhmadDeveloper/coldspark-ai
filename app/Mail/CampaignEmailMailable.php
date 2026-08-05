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

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6;">
{$body}
{$pixel}
</body>
</html>
HTML;
    }

    /**
     * Set custom headers for tracking.
     */
    public function headers(): \Illuminate\Mail\Mailables\Headers
    {
        return new \Illuminate\Mail\Mailables\Headers(
            text: [
                'X-Campaign-Id'       => (string) $this->campaignEmail->campaign_id,
                'X-Campaign-Email-Id' => (string) $this->campaignEmail->id,
            ],
        );
    }
}
