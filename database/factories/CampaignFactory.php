<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'user_id'             => User::factory(),
            'name'                => fake()->catchPhrase(),
            'status'              => 'active',
            'daily_limit'         => 30,
            'gap_minutes'         => 10,
            'start_date'          => now()->toDateString(),
            'working_hours_start' => '09:00:00',
            'working_hours_end'   => '17:00:00',
            'timezone'            => 'UTC',
            'followup_delay_1'    => 3,
            'followup_delay_2'    => 7,
            'stop_on_reply'       => true,
            'total_prospects'     => 0,
        ];
    }
}
