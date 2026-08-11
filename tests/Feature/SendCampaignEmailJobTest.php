<?php

namespace Tests\Feature;

use App\Jobs\SendCampaignEmailJob;
use App\Models\Campaign;
use App\Models\CampaignEmail;
use App\Models\Prospect;
use App\Models\Suppression;
use App\Services\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendCampaignEmailJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_a_due_email_and_marks_it_sent(): void
    {
        Mail::fake();

        $email = CampaignEmail::factory()->create();

        (new SendCampaignEmailJob($email->id))->handle(app(CampaignService::class), app(\App\Services\EmailSendingService::class));

        $email->refresh();
        $this->assertSame('sent', $email->status);
        $this->assertNotNull($email->sent_at);
        $this->assertNotNull($email->provider);
        Mail::assertSent(\App\Mail\CampaignEmailMailable::class, 1);
    }

    public function test_it_never_sends_the_same_email_twice(): void
    {
        Mail::fake();

        $email = CampaignEmail::factory()->create();
        $service = app(CampaignService::class);
        $sender  = app(\App\Services\EmailSendingService::class);

        (new SendCampaignEmailJob($email->id))->handle($service, $sender);
        (new SendCampaignEmailJob($email->id))->handle($service, $sender);

        Mail::assertSent(\App\Mail\CampaignEmailMailable::class, 1);
    }

    public function test_it_skips_suppressed_recipients_without_sending(): void
    {
        Mail::fake();

        $prospect = Prospect::factory()->create();
        $campaign = Campaign::factory()->for($prospect->user)->create();
        Suppression::create([
            'user_id' => $prospect->user_id,
            'email'   => $prospect->email,
            'reason'  => 'unsubscribed',
        ]);

        $email = CampaignEmail::factory()->for($campaign)->for($prospect)->create();

        (new SendCampaignEmailJob($email->id))->handle(app(CampaignService::class), app(\App\Services\EmailSendingService::class));

        Mail::assertNothingSent();
        $this->assertSame('skipped', $email->fresh()->status);
    }

    public function test_it_fails_immediately_for_a_missing_email_address_without_sending(): void
    {
        Mail::fake();

        $prospect = Prospect::factory()->create(['email' => null]);
        $email    = CampaignEmail::factory()->for($prospect)->create();

        (new SendCampaignEmailJob($email->id))->handle(app(CampaignService::class), app(\App\Services\EmailSendingService::class));

        Mail::assertNothingSent();
        $this->assertSame('failed', $email->fresh()->status);
    }

    public function test_it_does_not_send_for_a_paused_campaign(): void
    {
        Mail::fake();

        $campaign = Campaign::factory()->create(['status' => 'paused']);
        $email    = CampaignEmail::factory()->for($campaign)->create();

        (new SendCampaignEmailJob($email->id))->handle(app(CampaignService::class), app(\App\Services\EmailSendingService::class));

        Mail::assertNothingSent();
        $this->assertSame('scheduled', $email->fresh()->status);
    }
}
