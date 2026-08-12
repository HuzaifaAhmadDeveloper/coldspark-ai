<?php

namespace Tests\Feature;

use App\Models\CampaignEmail;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_database_rejects_a_duplicate_campaign_prospect_step_row(): void
    {
        $existing = CampaignEmail::factory()->create(['email_number' => 1]);

        $this->expectException(QueryException::class);

        CampaignEmail::factory()->create([
            'campaign_id'  => $existing->campaign_id,
            'prospect_id'  => $existing->prospect_id,
            'email_number' => 1,
        ]);
    }

    public function test_different_steps_for_the_same_prospect_are_allowed(): void
    {
        $existing = CampaignEmail::factory()->create(['email_number' => 1]);

        $second = CampaignEmail::factory()->create([
            'campaign_id'  => $existing->campaign_id,
            'prospect_id'  => $existing->prospect_id,
            'email_number' => 2,
        ]);

        $this->assertNotNull($second->id);
    }
}
