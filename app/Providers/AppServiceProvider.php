<?php

namespace App\Providers;

use App\Models\Asset;
use App\Policies\AssetPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(Asset::class, AssetPolicy::class);
        
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\MessageCreated::class,
            \App\Listeners\SendChatToN8n::class,
        );
    }
}
