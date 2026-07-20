<?php
namespace App\Providers;
use App\Listeners\CreateCreditOnRegister;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Force HTTPS in production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Event::listen(
            Registered::class,
            CreateCreditOnRegister::class,
        );
    }
}