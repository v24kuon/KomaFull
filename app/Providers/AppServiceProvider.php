<?php

namespace App\Providers;

use App\Actions\Fortify\CreateProvisionalMemberProfile;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
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
        Event::listen(function (Verified $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            app(CreateProvisionalMemberProfile::class)->createFor($event->user);
        });
    }
}
