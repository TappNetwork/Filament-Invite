<?php

namespace Tapp\FilamentInvite;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Livewire\Livewire;
use Tapp\FilamentInvite\Http\InviteMiddleware;

class FilamentInvitePlugin implements Plugin
{
    public const ID = 'filament-invite';

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {
        $panel->middleware([InviteMiddleware::class], isPersistent: true);

        Livewire::addPersistentMiddleware([InviteMiddleware::class]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function registerOnPanel(Panel $panel): void
    {
        if ($panel->hasPlugin(self::ID)) {
            return;
        }

        $panel->plugin(static::make());
    }
}
