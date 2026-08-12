<?php

namespace App\Jobs;

use App\Exceptions\Mail\PermanentMailException;
use App\Mail\CampaignEmailMailable;
use App\Models\CampaignEmail;
use App\Services\CampaignService;
use App\Services\EmailSendingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(public int $campaignEmailId) {}

    /**
     * Throttles actual sending to services.email.rate_per_second (the
     * "ses-sending" limiter registered in AppServiceProvider) regardless of
     * how many jobs the queue worker pulls at once — dispatch-due can queue
     * a whole batch in one shot; this is what keeps the provider's
     * per-second cap from ever being exceeded. Jobs that hit the limit are
     * released back onto the queue to retry shortly, not counted against $tries.
     */
    public function middleware(): array
    {
        return [new RateLimited('ses-sending')];
    }

    public function handle(CampaignService $service, EmailSendingService $sender): void
    {
        $email = CampaignEmail::with(['campaign', 'prospect'])->find($this->campaignEmailId);
        if (!$email) return;

        // Campaign may have been paused/cancelled after this job was queued.
        if (!in_array($email->campaign->status, ['active', 'scheduled'])) return;

        // A "scheduled" campaign becomes "active" the moment its first email
        // actually starts sending — not at creation time, and not just
        // because its start_date arrived (that's still SCHEDULED until real
        // sending begins).
        if ($email->campaign->status === 'scheduled') {
            $service->activateIfScheduled($email->campaign);
        }

        // Already handled (e.g. duplicate dispatch) — don't send twice.
        if (!in_array($email->status, ['scheduled', 'sending'])) return;

        $recipient = $email->prospect?->email;
        if (!$recipient || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $service->markFailed($email, 'Prospect has no valid email address.');
            $service->checkCompletion($email->campaign);
            return;
        }

        // Authoritative, cross-campaign check: unsubscribed, hard-bounced, complained,
        // or manually suppressed anywhere in this account — never just this campaign.
        if ($service->isSuppressed($recipient, $email->campaign->user_id)) {
            $service->markSkipped($email, 'Recipient is on the suppression list');
            $service->checkCompletion($email->campaign);
            return;
        }

        $service->markSending($email);

        try {
            $result = $sender->send(new CampaignEmailMailable($email), $recipient);

            $service->markSent($email, $result->messageId, $result->provider);
        } catch (PermanentMailException $e) {
            // Every configured provider rejected it for a reason retrying won't fix
            // (invalid address, unverified SES sandbox recipient, suppressed, etc).
            $service->markFailed($email, $e->getMessage());
            $service->checkCompletion($email->campaign);
            return;
        } catch (\Throwable $e) {
            $service->recordLog($email->campaign, 'send_error', "CampaignEmail #{$email->id}: " . $e->getMessage());
            throw $e; // let the queue retry per $tries/$backoff — failed() handles the final attempt
        }

        $service->checkCompletion($email->campaign->fresh());
    }

    public function failed(\Throwable $e): void
    {
        $email = CampaignEmail::with('campaign')->find($this->campaignEmailId);
        if (!$email) return;

        app(CampaignService::class)->markFailed($email, $e->getMessage());
        app(CampaignService::class)->checkCompletion($email->campaign);
    }
}
