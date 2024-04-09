<?php

namespace Tapp\FilamentInvite;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentInviteServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-invite';

    public static string $viewNamespace = 'filament-invite';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */

        $package->name(static::$name);
    }

    public function packageBooted(): void
    {
        // Listeners
        Event::listen(function (PasswordReset $event) {
            if (! $event->user->hasVerifiedEmail()) {
                $event->user->markEmailAsVerified();
            }
        });
    }

    protected function getAssetPackageName(): ?string
    {
        return 'tapp/filament-invite';
    }
}
