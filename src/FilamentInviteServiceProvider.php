<?php

namespace Tapp\FilamentInvite;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Tapp\FilamentInvite\Http\InviteMiddleware;
use Livewire\Livewire;

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

        $package->name(static::$name)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile();
            });

        $package->hasConfigFile();
    }

    public function packageBooted(): void
    {
        // Listeners
        Event::listen(function (PasswordReset $event) {
            if (! $event->user->hasVerifiedEmail()) {
                $event->user->markEmailAsVerified();
            }

            session()->forget('invite');
        });

        $router = $this->app['router'];

        $router->pushMiddlewareToGroup('web', InviteMiddleware::class);

        Livewire::addPersistentMiddleware([
            InviteMiddleware::class,
        ]);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'tapp/filament-invite';
    }
}
