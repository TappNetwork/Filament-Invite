<?php

namespace Tapp\FilamentInvite;

use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tapp\FilamentInvite\Testing\TestsFilamentInvite;

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

    public function packageRegistered(): void
    {
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-invite/{$file->getFilename()}"),
                ], 'filament-invite-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsFilamentInvite());

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
