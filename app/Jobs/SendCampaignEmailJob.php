<?php

namespace App\Jobs;

use App\Mail\CampaignEmailMailable;
use App\Models\CampaignEmail;
use App\Services\CampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(public int $campaignEmailId) {}

    public function handle(CampaignService $service): void
    {
        $email = CampaignEmail::with(['campaign', 'prospect'])->find($this->campaignEmailId);
        if (!$email) return;

        // Campaign may have been paused/cancelled after this job was queued.
        if ($email->campaign->status !== 'active') return;

        // Already handled (e.g. duplicate dispatch) — don't send twice.
        if (!in_array($email->status, ['scheduled', 'sending'])) return;

        $recipient = $email->prospect?->email;
        if (!$recipient || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $service->markFailed($email, 'Prospect has no valid email address.');
            $service->checkCompletion($email->campaign);
            return;
        }

        $service->markSending($email);

        try {
            Mail::to($recipient)->send(new CampaignEmailMailable($email));

            $service->markSent($email, (string) \Illuminate\Support\Str::uuid());
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
