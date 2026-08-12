<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignEmail;
use App\Models\Prospect;
use App\Services\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignSuppressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_hard_bounce_suppresses_the_recipient_across_every_campaign(): void
    {
        $service  = app(CampaignService::class);
        $prospect = Prospect::factory()->create();

        // Two separate campaigns for the same prospect.
        $campaignA = Campaign::factory()->for($prospect->user)->create();
        $campaignB = Campaign::factory()->for($prospect->user)->create();
        $emailA    = CampaignEmail::factory()->for($campaignA)->for($prospect)->create(['status' => 'sending']);
        $emailB    = CampaignEmail::factory()->for($campaignB)->for($prospect)->create(['status' => 'scheduled']);

        $service->markBounced($emailA, permanent: true);

        $this->assertTrue($service->isSuppressed($prospect->email, $prospect->user_id));
        // The bounce didn't happen on campaign B's email, but the prospect is
        // now globally suppressed — SendCampaignEmailJob is what actually
        // skips it at send time; this asserts the suppression list itself.
        $this->assertDatabaseHas('suppressions', [
            'user_id' => $prospect->user_id,
            'email'   => strtolower($prospect->email),
            'reason'  => 'hard_bounce',
        ]);
    }

    public function test_soft_bounce_does_not_suppress_the_recipient(): void
    {
        $service = app(CampaignService::class);
        $email   = CampaignEmail::factory()->create();

        $service->markBounced($email, permanent: false);

        $this->assertFalse($service->isSuppressed($email->prospect->email, $email->prospect->user_id));
        $this->assertSame('failed', $email->fresh()->status); // this attempt still failed
        $this->assertDatabaseMissing('suppressions', ['email' => strtolower($email->prospect->email)]);
    }

    public function test_unsubscribe_suppresses_and_cancels_all_pending_emails(): void
    {
        $service  = app(CampaignService::class);
        $prospect = Prospect::factory()->create();
        $campaign = Campaign::factory()->for($prospect->user)->create();

        $pending1 = CampaignEmail::factory()->for($campaign)->for($prospect)->create(['email_number' => 1, 'status' => 'scheduled']);
        $pending2 = CampaignEmail::factory()->for($campaign)->for($prospect)->create(['email_number' => 2, 'status' => 'scheduled']);
        $alreadySent = CampaignEmail::factory()->for($campaign)->for($prospect)->create(['email_number' => 3, 'status' => 'sent']);

        $cancelled = $service->markUnsubscribed($prospect);

        $this->assertSame(2, $cancelled);
        $this->assertSame('skipped', $pending1->fresh()->status);
        $this->assertSame('skipped', $pending2->fresh()->status);
        $this->assertSame('sent', $alreadySent->fresh()->status); // history is never rewritten
        $this->assertTrue($prospect->fresh()->unsubscribed);
        $this->assertTrue($service->isSuppressed($prospect->email, $prospect->user_id));
    }

    public function test_reply_cancels_future_followups_when_stop_on_reply_is_enabled(): void
    {
        $service  = app(CampaignService::class);
        $campaign = Campaign::factory()->create(['stop_on_reply' => true]);
        $prospect = Prospect::factory()->for($campaign->user)->create();

        $opener   = CampaignEmail::factory()->for($campaign)->for($prospect)->create(['email_number' => 1, 'status' => 'sent']);
        $followup = CampaignEmail::factory()->for($campaign)->for($prospect)->create(['email_number' => 2, 'status' => 'scheduled']);

        $service->markReply($opener);

        $this->assertSame('skipped', $followup->fresh()->status);
    }

    public function test_reply_leaves_followups_alone_when_stop_on_reply_is_disabled(): void
    {
        $service  = app(CampaignService::class);
        $campaign = Campaign::factory()->create(['stop_on_reply' => false]);
        $prospect = Prospect::factory()->for($campaign->user)->create();

        $opener   = CampaignEmail::factory()->for($campaign)->for($prospect)->create(['email_number' => 1, 'status' => 'sent']);
        $followup = CampaignEmail::factory()->for($campaign)->for($prospect)->create(['email_number' => 2, 'status' => 'scheduled']);

        $service->markReply($opener);

        $this->assertSame('scheduled', $followup->fresh()->status);
    }
}
