<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\ResponseCache\Events\CacheMissedEvent;
use Spatie\ResponseCache\Events\ResponseCacheHitEvent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') == 'local' && config('responsecache.enabled')) {

            Event::listen(ResponseCacheHitEvent::class, function ($event) {
                logger('Cache HIT: '.$event->request->url());
            });

            Event::listen(CacheMissedEvent::class, function ($event) {
                logger('Cache MISS: '.$event->request->url());
            });
        }
    }
}
