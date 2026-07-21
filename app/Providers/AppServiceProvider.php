<?php
namespace App\Providers;
use App\Listeners\CreateCreditOnRegister;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Features\SupportFileUploads\FileUploadController;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Event::listen(
            Registered::class,
            CreateCreditOnRegister::class,
        );

        // Fix Livewire file upload on Railway
        \Livewire\Livewire::setUpdateRoute(function($handle) {
            return \Illuminate\Support\Facades\Route::post(
                '/livewire/update',
                $handle
            )->middleware('web');
        });

        \Livewire\Livewire::setScriptRoute(function($handle) {
            return \Illuminate\Support\Facades\Route::get(
                '/livewire/livewire.min.js',
                $handle
            );
        });
    }
}