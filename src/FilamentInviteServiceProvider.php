<?php

namespace Tapp\FilamentInvite;

use Filament\Facades\Filament;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tapp\FilamentInvite\Http\InviteMiddleware;

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
            })
            ->hasConfigFile()
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        Event::listen(function (PasswordReset $event) {
            if (! $event->user->hasVerifiedEmail()) {
                $event->user->markEmailAsVerified();
            }

            session()->forget('invite');
        });

        // Keep support for non-panel password.reset routes (e.g. Laravel's default broker routes).
        $this->app['router']->pushMiddlewareToGroup('web', InviteMiddleware::class);

        Livewire::addPersistentMiddleware([
            InviteMiddleware::class,
        ]);

        // Filament panels do not use the `web` group — attach InviteMiddleware to every
        // panel after it is fully configured so it runs after StartSession.
        $this->app->booted(function (): void {
            foreach (Filament::getPanels() as $panel) {
                FilamentInvitePlugin::registerOnPanel($panel);
            }
        });
    }

    protected function getAssetPackageName(): ?string
    {
        return 'tapp/filament-invite';
    }
}
