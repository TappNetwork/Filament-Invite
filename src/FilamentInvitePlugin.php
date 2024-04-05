<?php

namespace Tapp\FilamentInvite;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentInvitePlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-invite';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            // InviteResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
