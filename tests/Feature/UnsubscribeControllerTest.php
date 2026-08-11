<?php

namespace Tests\Feature;

use App\Models\CampaignEmail;
use App\Models\Prospect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnsubscribeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_shows_a_confirmation_page_without_unsubscribing(): void
    {
        $email = CampaignEmail::factory()->create();

        $response = $this->get("/unsubscribe/{$email->tracking_token}");

        $response->assertOk();
        $this->assertFalse($email->prospect->fresh()->unsubscribed);
    }

    public function test_post_unsubscribes_and_cancels_pending_emails(): void
    {
        $prospect = Prospect::factory()->create();
        $target   = CampaignEmail::factory()->for($prospect)->create(['status' => 'scheduled']);
        $other    = CampaignEmail::factory()->for($prospect)->create(['status' => 'scheduled']);

        $response = $this->post("/unsubscribe/{$target->tracking_token}");

        $response->assertOk();
        $this->assertTrue($prospect->fresh()->unsubscribed);
        $this->assertSame('skipped', $target->fresh()->status);
        $this->assertSame('skipped', $other->fresh()->status);
    }

    public function test_unknown_token_does_not_error(): void
    {
        // Route constrains tokens to [A-Za-z0-9]+ (matching tracking_token's
        // format) — this one is well-formed but doesn't exist in the DB.
        $response = $this->get('/unsubscribe/doesnotexisttoken12345');

        $response->assertOk();
    }
}
