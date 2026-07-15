<?php
namespace App\Listeners;
use App\Models\Credit;
use Illuminate\Auth\Events\Registered;

class CreateCreditOnRegister
{
    public function handle(Registered $event): void
    {
        Credit::firstOrCreate(
            ['user_id' => $event->user->id],
            ['balance' => 100]
        );
    }
}