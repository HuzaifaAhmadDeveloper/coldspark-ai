<?php

namespace Tests\Feature;

use App\Models\CampaignEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_pixel_marks_the_email_opened(): void
    {
        $email = CampaignEmail::factory()->create();

        $response = $this->get("/t/o/{$email->tracking_token}.gif");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/gif');
        $this->assertTrue($email->fresh()->opened);
    }

    public function test_click_marks_clicked_and_redirects_to_the_original_url(): void
    {
        $email = CampaignEmail::factory()->create();

        $response = $this->get("/t/c/{$email->tracking_token}?url=" . urlencode('https://example.com/pricing'));

        $response->assertRedirect('https://example.com/pricing');
        $this->assertTrue($email->fresh()->clicked);
    }

    public function test_click_falls_back_to_app_url_for_an_unsafe_redirect_target(): void
    {
        $email = CampaignEmail::factory()->create();

        $response = $this->get("/t/c/{$email->tracking_token}?url=" . urlencode('javascript:alert(1)'));

        $response->assertRedirect(config('app.url'));
    }

    public function test_pixel_still_returns_a_valid_image_for_an_unknown_token(): void
    {
        $response = $this->get('/t/o/unknowntoken12345.gif');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/gif');
    }
}
