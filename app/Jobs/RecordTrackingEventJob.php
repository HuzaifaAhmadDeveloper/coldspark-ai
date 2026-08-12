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
        // SES/SNS's own notification MessageId (or an equivalent ID from another
        // ESP) — see CampaignService::recordEvent(). Null for pixel/click events,
        // which have no provider-assigned ID and don't need this protection.
        public ?string $providerEventId = null,
    ) {}

    public function handle(CampaignService $service): void
    {
        $email = $service->findByTrackingToken($this->trackingToken);
        if (!$email) return;

        match ($this->eventType) {
            'opened'       => $service->markOpened($email),
            'clicked'      => $service->markClicked($email),
            'delivered'    => $service->markDelivered($email, $this->providerEventId),
            // Hard bounce: address is bad, will never work — suppress everywhere.
            'bounced'      => $service->markBounced($email, permanent: true, providerEventId: $this->providerEventId),
            // Soft/transient bounce: mailbox full, greylisted, etc — this attempt
            // failed, but don't blacklist the address; let the retry policy work.
            'soft_bounced' => $service->markBounced($email, permanent: false, providerEventId: $this->providerEventId),
            'replied'      => $service->markReply($email, $this->providerEventId),
            'complaint'    => $this->handleComplaint($service, $email),
            'unsubscribed' => $service->markUnsubscribed($email->prospect, providerEventId: $this->providerEventId),
            default        => $service->recordEvent($email, $this->eventType, $this->meta, $this->providerEventId),
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
        $service->markUnsubscribed($email->prospect, 'complaint', $this->providerEventId);
        $service->markBounced($email, permanent: false, providerEventId: $this->providerEventId);
    }
}
