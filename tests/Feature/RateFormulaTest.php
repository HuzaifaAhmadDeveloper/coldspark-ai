<?php

namespace Tests\Feature;

use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateFormulaTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_click_reply_rates_use_delivered_as_the_denominator(): void
    {
        $campaign = Campaign::factory()->create([
            'emails_sent'      => 100,
            'emails_delivered' => 80,
            'emails_opened'    => 40,
            'emails_clicked'   => 20,
            'replies_received' => 10,
            'emails_bounced'   => 5,
        ]);

        $this->assertSame(50.0, $campaign->open_rate);   // 40/80, not 40/100
        $this->assertSame(25.0, $campaign->click_rate);  // 20/80
        $this->assertSame(12.5, $campaign->reply_rate);  // 10/80
        $this->assertSame(80.0, $campaign->delivery_rate); // 80/100
        $this->assertSame(5.0, $campaign->bounce_rate);   // 5/100 — Sent, not Delivered
    }

    public function test_rates_are_zero_not_divide_by_zero_when_nothing_delivered_yet(): void
    {
        $campaign = Campaign::factory()->create([
            'emails_sent' => 50, 'emails_delivered' => 0, 'emails_opened' => 0,
        ]);

        $this->assertSame(0.0, $campaign->open_rate);
        $this->assertSame(0.0, $campaign->delivery_rate);
    }
}
