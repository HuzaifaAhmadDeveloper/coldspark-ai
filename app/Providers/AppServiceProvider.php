<?php
namespace App\Providers;
use App\Listeners\CreateCreditOnRegister;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(
            Registered::class,
            CreateCreditOnRegister::class,
        );
    }
}