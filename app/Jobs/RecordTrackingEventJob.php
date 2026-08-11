<?php

namespace App\Jobs;

use App\Services\CampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordTrackingEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $trackingToken,
        public string $eventType, // opened|clicked|bounced|soft_bounced|replied|delivered|complaint|unsubscribed
        public array $meta = [],
    ) {}

    public function handle(CampaignService $service): void
    {
        $email = $service->findByTrackingToken($this->trackingToken);
        if (!$email) return;

        match ($this->eventType) {
            'opened'       => $service->markOpened($email),
            'clicked'      => $service->markClicked($email),
            'delivered'    => $service->markDelivered($email),
            // Hard bounce: address is bad, will never work — suppress everywhere.
            'bounced'      => $service->markBounced($email, permanent: true),
            // Soft/transient bounce: mailbox full, greylisted, etc — this attempt
            // failed, but don't blacklist the address; let the retry policy work.
            'soft_bounced' => $service->markBounced($email, permanent: false),
            'replied'      => $service->markReply($email),
            'complaint'    => $this->handleComplaint($service, $email),
            'unsubscribed' => $service->markUnsubscribed($email->prospect),
            default        => $service->recordEvent($email, $this->eventType, $this->meta),
        };
    }

    /**
     * A spam complaint is a stronger signal than a bounce — count it toward
     * deliverability stats AND immediately suppress all future sends to them.
     * Suppress first (reason=complaint) so that's the reason recorded, then
     * mark this specific send as failed without re-suppressing.
     */
    private function handleComplaint(CampaignService $service, \App\Models\CampaignEmail $email): void
    {
        $service->markUnsubscribed($email->prospect, 'complaint');
        $service->markBounced($email, permanent: false);
    }
}
