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
        public string $eventType, // opened|clicked|bounced|replied
        public array $meta = [],
    ) {}

    public function handle(CampaignService $service): void
    {
        $email = $service->findByTrackingToken($this->trackingToken);
        if (!$email) return;

        match ($this->eventType) {
            'opened'  => $service->markOpened($email),
            'clicked' => $service->markClicked($email),
            'bounced' => $service->markBounced($email),
            'replied' => $service->markReply($email),
            default   => $service->recordEvent($email, $this->eventType, $this->meta),
        };
    }
}
