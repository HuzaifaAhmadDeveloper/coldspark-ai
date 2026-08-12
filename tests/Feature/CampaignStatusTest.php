<?php

namespace Tests\Feature;

use App\Jobs\SendCampaignEmailJob;
use App\Livewire\CampaignCreate;
use App\Models\Campaign;
use App\Models\CampaignEmail;
use App\Models\User;
use App\Services\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CampaignStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_campaign_scheduled_for_the_future_is_not_marked_active(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(CampaignCreate::class)
            ->set('name', 'Future Campaign')
            ->set('start_date', now()->addDays(5)->toDateString())
            ->set('timezone', 'UTC')
            ->set('generatedEmails', [[
                'prospect' => [
                    'name' => 'Jane Doe', 'email' => 'jane@example.com', 'company' => 'Acme',
                    'role' => 'CEO', 'industry' => 'Tech', 'pain_point' => 'Growth', 'personal_note' => '',
                ],
                'subject1' => 'Hi', 'email1' => 'Body 1', 'email2' => 'Body 2', 'email3' => 'Body 3',
                'status' => 'ready',
            ]])
            ->call('launchCampaign');

        $campaign = Campaign::where('name', 'Future Campaign')->firstOrFail();
        $this->assertSame('scheduled', $campaign->status);
    }

    public function test_a_campaign_starting_today_is_marked_active_immediately(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(CampaignCreate::class)
            ->set('name', 'Today Campaign')
            ->set('start_date', now()->toDateString())
            ->set('timezone', 'UTC')
            ->set('generatedEmails', [[
                'prospect' => [
                    'name' => 'John Doe', 'email' => 'john@example.com', 'company' => 'Acme',
                    'role' => 'CEO', 'industry' => 'Tech', 'pain_point' => 'Growth', 'personal_note' => '',
                ],
                'subject1' => 'Hi', 'email1' => 'Body 1', 'email2' => 'Body 2', 'email3' => 'Body 3',
                'status' => 'ready',
            ]])
            ->call('launchCampaign');

        $campaign = Campaign::where('name', 'Today Campaign')->firstOrFail();
        $this->assertSame('active', $campaign->status);
    }

    public function test_scheduled_campaign_is_picked_up_by_due_for_sending_once_its_time_arrives_and_becomes_active(): void
    {
        Mail::fake();

        $campaign = Campaign::factory()->create(['status' => 'scheduled']);
        $email    = CampaignEmail::factory()->for($campaign)->scheduledInPast()->create(['email_number' => 1, 'status' => 'scheduled']);
        // A still-future follow-up so the campaign has real work left after
        // this send — otherwise checkCompletion() correctly (and separately)
        // flips it straight on to COMPLETED in the same job run, which would
        // make this test about completion, not about the SCHEDULED→ACTIVE step.
        CampaignEmail::factory()->for($campaign)->create(['email_number' => 2, 'status' => 'scheduled', 'scheduled_at' => now()->addDays(3)]);

        $this->assertContains($email->id, CampaignEmail::dueForSending()->pluck('id')->all());

        (new SendCampaignEmailJob($email->id))->handle(app(CampaignService::class), app(\App\Services\EmailSendingService::class));

        $this->assertSame('active', $campaign->fresh()->status);
        $this->assertSame('sent', $email->fresh()->status);
    }

    public function test_a_paused_scheduled_campaign_is_not_due_for_sending(): void
    {
        $campaign = Campaign::factory()->create(['status' => 'paused']);
        CampaignEmail::factory()->for($campaign)->scheduledInPast()->create(['status' => 'scheduled']);

        $this->assertSame(0, CampaignEmail::dueForSending()->count());
    }

    public function test_campaign_marked_failed_when_every_email_fails_and_completed_when_at_least_one_succeeds(): void
    {
        $service = app(CampaignService::class);

        $failedCampaign = Campaign::factory()->create(['status' => 'active']);
        CampaignEmail::factory()->for($failedCampaign)->create(['status' => 'failed']);
        CampaignEmail::factory()->for($failedCampaign)->create(['status' => 'failed']);
        $service->checkCompletion($failedCampaign);
        $this->assertSame('failed', $failedCampaign->fresh()->status);

        $okCampaign = Campaign::factory()->create(['status' => 'active']);
        CampaignEmail::factory()->for($okCampaign)->create(['status' => 'sent']);
        CampaignEmail::factory()->for($okCampaign)->create(['status' => 'failed']);
        $service->checkCompletion($okCampaign);
        $this->assertSame('completed', $okCampaign->fresh()->status);
    }
}
