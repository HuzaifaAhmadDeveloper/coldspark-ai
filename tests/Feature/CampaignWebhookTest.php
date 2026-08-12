<?php

namespace Tests\Feature;

use App\Models\CampaignEmail;
use App\Models\EmailEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.email_webhook.secret' => 'test-secret']);
    }

    private function snsNotification(array $innerMessage): array
    {
        return [
            'Type'      => 'Notification',
            'MessageId' => 'sns-' . md5(json_encode($innerMessage)),
            'Message'   => json_encode($innerMessage),
        ];
    }

    public function test_webhook_rejects_requests_without_the_correct_key(): void
    {
        $response = $this->postJson('/webhooks/campaign-events?key=wrong', []);
        $response->assertStatus(403);
    }

    public function test_ses_delivery_notification_marks_the_email_delivered(): void
    {
        $email = CampaignEmail::factory()->create(['status' => 'sent']);

        $payload = $this->snsNotification([
            'notificationType' => 'Delivery',
            'mail' => ['messageId' => 'abc123', 'headers' => [
                ['name' => 'X-Campaign-Email-Id', 'value' => (string) $email->id],
            ]],
        ]);

        $this->postJson('/webhooks/campaign-events?key=test-secret', $payload)->assertOk();

        $email->refresh();
        $this->assertTrue($email->delivered);
        $this->assertDatabaseHas('email_events', [
            'campaign_email_id' => $email->id,
            'event_type'        => 'delivered',
        ]);
    }

    public function test_duplicate_sns_delivery_notification_does_not_create_a_second_event(): void
    {
        $email = CampaignEmail::factory()->create(['status' => 'sent']);

        $payload = $this->snsNotification([
            'notificationType' => 'Delivery',
            'mail' => ['messageId' => 'abc123', 'headers' => [
                ['name' => 'X-Campaign-Email-Id', 'value' => (string) $email->id],
            ]],
        ]);

        // SNS redelivers with the identical MessageId on retry — simulate that exactly.
        $this->postJson('/webhooks/campaign-events?key=test-secret', $payload)->assertOk();
        $this->postJson('/webhooks/campaign-events?key=test-secret', $payload)->assertOk();

        $this->assertSame(1, EmailEvent::where('campaign_email_id', $email->id)
            ->where('event_type', 'delivered')->count());
    }

    public function test_ses_hard_bounce_suppresses_the_recipient_globally(): void
    {
        $email = CampaignEmail::factory()->create(['status' => 'sent']);

        $payload = $this->snsNotification([
            'notificationType' => 'Bounce',
            'bounce' => ['bounceType' => 'Permanent', 'feedbackId' => 'bounce-1'],
            'mail' => ['messageId' => 'abc123', 'headers' => [
                ['name' => 'X-Campaign-Email-Id', 'value' => (string) $email->id],
            ]],
        ]);

        $this->postJson('/webhooks/campaign-events?key=test-secret', $payload)->assertOk();

        $this->assertTrue($email->fresh()->bounced);
        $this->assertDatabaseHas('suppressions', [
            'email'  => strtolower($email->prospect->email),
            'reason' => 'hard_bounce',
        ]);
    }

    public function test_ses_transient_bounce_does_not_suppress_the_recipient(): void
    {
        $email = CampaignEmail::factory()->create(['status' => 'sent']);

        $payload = $this->snsNotification([
            'notificationType' => 'Bounce',
            'bounce' => ['bounceType' => 'Transient', 'feedbackId' => 'bounce-2'],
            'mail' => ['messageId' => 'abc123', 'headers' => [
                ['name' => 'X-Campaign-Email-Id', 'value' => (string) $email->id],
            ]],
        ]);

        $this->postJson('/webhooks/campaign-events?key=test-secret', $payload)->assertOk();

        $this->assertTrue($email->fresh()->bounced); // this send did fail
        $this->assertDatabaseMissing('suppressions', ['email' => strtolower($email->prospect->email)]);
    }

    public function test_ses_complaint_suppresses_the_recipient(): void
    {
        $email = CampaignEmail::factory()->create(['status' => 'sent']);

        $payload = $this->snsNotification([
            'notificationType' => 'Complaint',
            'complaint' => ['feedbackId' => 'complaint-1'],
            'mail' => ['messageId' => 'abc123', 'headers' => [
                ['name' => 'X-Campaign-Email-Id', 'value' => (string) $email->id],
            ]],
        ]);

        $this->postJson('/webhooks/campaign-events?key=test-secret', $payload)->assertOk();

        $this->assertDatabaseHas('suppressions', [
            'email'  => strtolower($email->prospect->email),
            'reason' => 'complaint',
        ]);
    }

    public function test_ses_inbound_received_reply_marks_replied_and_cancels_followups(): void
    {
        $campaign = \App\Models\Campaign::factory()->create(['stop_on_reply' => true]);
        $prospect = \App\Models\Prospect::factory()->for($campaign->user)->create();

        $opener   = CampaignEmail::factory()->for($campaign)->for($prospect)
            ->create(['email_number' => 1, 'status' => 'sent']);
        $followup = CampaignEmail::factory()->for($campaign)->for($prospect)
            ->create(['email_number' => 2, 'status' => 'scheduled']);

        // SES inbound receiving: notificationType "Received", recipient in mail.destination.
        $payload = $this->snsNotification([
            'notificationType' => 'Received',
            'mail' => [
                'messageId'   => 'inbound-1',
                'destination' => ["reply+{$opener->tracking_token}@reply.ranksol.net"],
            ],
            'receipt' => ['recipients' => ["reply+{$opener->tracking_token}@reply.ranksol.net"]],
        ]);

        $this->postJson('/webhooks/campaign-events?key=test-secret', $payload)->assertOk();

        $this->assertTrue($opener->fresh()->replied);
        $this->assertSame('skipped', $followup->fresh()->status);
    }

    public function test_sns_subscription_confirmation_is_logged_and_not_treated_as_an_event(): void
    {
        $payload = [
            'Type'          => 'SubscriptionConfirmation',
            'TopicArn'      => 'arn:aws:sns:us-east-1:123:test',
            'SubscribeURL'  => 'https://sns.us-east-1.amazonaws.com/?Action=ConfirmSubscription&Token=abc',
        ];

        $response = $this->postJson('/webhooks/campaign-events?key=test-secret', $payload);

        $response->assertOk();
        $response->assertJson(['received' => 1, 'handled' => 0]);
        $this->assertSame(0, EmailEvent::count());
    }
}
