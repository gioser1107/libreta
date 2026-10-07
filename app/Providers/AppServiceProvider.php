<?php

namespace App\Providers;

use App\Models\User;
use App\Passkeys\PlatformRegistrationOptions;
use App\Support\Permissions;
use Illuminate\Auth\Events\Registered;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GenerateRegistrationOptions::class, PlatformRegistrationOptions::class);
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        Event::listen(Registered::class, function (Registered $event): void {
            if ($event->user instanceof User) {
                Permissions::assignUsuario($event->user);
            }
        });
    }
}
