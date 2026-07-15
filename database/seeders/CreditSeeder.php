<?php
namespace Database\Seeders;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Database\Seeder;

class CreditSeeder extends Seeder
{
    public function run(): void
    {
        User::all()->each(function ($user) {
            Credit::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 100]
            );
        });
    }
}