<?php

namespace Tests\Feature;

use App\Jobs\SendCampaignEmailJob;
use App\Models\CampaignEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_job_is_rate_limited(): void
    {
        $job = new SendCampaignEmailJob(1);

        $middleware = $job->middleware();

        $this->assertCount(1, $middleware);
        $this->assertInstanceOf(RateLimited::class, $middleware[0]);
    }

    public function test_dispatch_due_never_exceeds_the_configured_daily_limit(): void
    {
        // Prevent the queue's "sync" test driver from actually executing the
        // dispatched jobs — this test is only about dispatch-due's own
        // pre-dispatch bookkeeping (which rows it flips to "sending" and how
        // many), not about what SendCampaignEmailJob does afterward.
        Queue::fake();
        config(['services.email.daily_limit' => 3]);

        // 2 already sent today (counts against the cap) + 5 more due right now.
        CampaignEmail::factory()->count(2)->create(['status' => 'sent', 'updated_at' => now()]);
        CampaignEmail::factory()->count(5)->scheduledInPast()->create(['status' => 'scheduled']);

        $this->artisan('campaigns:dispatch-due')->assertSuccessful();

        // Cap is 3, 2 already counted as sent today, so only 1 more may move to "sending".
        $this->assertSame(1, CampaignEmail::where('status', 'sending')->count());
        $this->assertSame(4, CampaignEmail::where('status', 'scheduled')->count());
        Queue::assertPushed(SendCampaignEmailJob::class, 1);
    }

    public function test_dispatch_due_defers_everything_once_the_daily_limit_is_already_reached(): void
    {
        Queue::fake();
        config(['services.email.daily_limit' => 2]);

        CampaignEmail::factory()->count(2)->create(['status' => 'sent', 'updated_at' => now()]);
        CampaignEmail::factory()->count(3)->scheduledInPast()->create(['status' => 'scheduled']);

        $this->artisan('campaigns:dispatch-due')->assertSuccessful();

        $this->assertSame(0, CampaignEmail::where('status', 'sending')->count());
        $this->assertSame(3, CampaignEmail::where('status', 'scheduled')->count());
        Queue::assertNotPushed(SendCampaignEmailJob::class);
    }
}
