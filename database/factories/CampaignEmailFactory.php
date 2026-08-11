<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignEmail;
use App\Models\Prospect;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CampaignEmail>
 */
class CampaignEmailFactory extends Factory
{
    protected $model = CampaignEmail::class;

    public function definition(): array
    {
        return [
            'campaign_id'    => Campaign::factory(),
            'prospect_id'    => Prospect::factory(),
            'email_number'   => 1,
            'subject'        => fake()->sentence(),
            'body'           => fake()->paragraphs(3, true),
            'scheduled_at'   => now()->addMinutes(10),
            'status'         => 'scheduled',
            'tracking_token' => Str::random(32),
        ];
    }

    public function scheduledInPast(): static
    {
        return $this->state(fn () => ['scheduled_at' => now()->subMinutes(5)]);
    }
}
