<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignEmail;
use App\Models\Prospect;
use App\Services\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_emails_are_never_placed_in_the_past(): void
    {
        $service  = app(CampaignService::class);
        $campaign = Campaign::factory()->create([
            'timezone'            => 'UTC',
            'working_hours_start' => '09:00:00',
            'working_hours_end'   => '17:00:00',
            'gap_minutes'         => 10,
            'daily_limit'         => 30,
        ]);
        $prospect = Prospect::factory()->for($campaign->user)->create();

        $service->scheduleEmails($campaign, [[
            'prospect_id' => $prospect->id,
            'subject1'    => 'Hi',
            'email1'      => 'Body 1',
            'email2'      => 'Body 2',
            'email3'      => 'Body 3',
            'status'      => 'ready',
        ]]);

        $emails = CampaignEmail::where('campaign_id', $campaign->id)->get();
        $this->assertCount(3, $emails);

        foreach ($emails as $email) {
            $this->assertTrue(
                $email->scheduled_at->isFuture(),
                "Email #{$email->email_number} was scheduled in the past: {$email->scheduled_at}"
            );
        }

        // Email 1 must come before its two follow-ups.
        $opener = $emails->firstWhere('email_number', 1);
        $fu1    = $emails->firstWhere('email_number', 2);
        $fu2    = $emails->firstWhere('email_number', 3);
        $this->assertTrue($opener->scheduled_at->lt($fu1->scheduled_at));
        $this->assertTrue($fu1->scheduled_at->lte($fu2->scheduled_at));
    }

    public function test_daily_limit_rolls_remaining_sends_to_the_next_working_day(): void
    {
        $service  = app(CampaignService::class);
        $campaign = Campaign::factory()->create([
            'timezone'            => 'UTC',
            'working_hours_start' => '09:00:00',
            'working_hours_end'   => '17:00:00',
            'gap_minutes'         => 10,
            'daily_limit'         => 1, // force a rollover after the very first prospect
            'start_date'          => now()->addDay()->toDateString(), // avoid "today" edge cases
        ]);
        $prospects = Prospect::factory()->for($campaign->user)->count(2)->create();

        $items = $prospects->map(fn ($p) => [
            'prospect_id' => $p->id,
            'subject1'    => 'Hi',
            'email1'      => 'Body 1',
            'email2'      => 'Body 2',
            'email3'      => 'Body 3',
            'status'      => 'ready',
        ])->all();

        $service->scheduleEmails($campaign, $items);

        $openers = CampaignEmail::where('campaign_id', $campaign->id)
            ->where('email_number', 1)->orderBy('scheduled_at')->get();

        $this->assertCount(2, $openers);
        $this->assertFalse(
            $openers[0]->scheduled_at->isSameDay($openers[1]->scheduled_at),
            'Second prospect should have rolled to a different day once the daily limit of 1 was hit.'
        );
    }
}
