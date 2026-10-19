<?php

namespace Tapp\FilamentInvite\Http;

use Closure;
use Filament\Exceptions\NoDefaultPanelSetException;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use ReflectionProperty;

class InviteMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $routeName = $request->route()?->getName();

        if ($this->isPasswordResetRoute($routeName) && $request->has('invite')) {
            session()->put('invite', true);
        }

        if (session()->has('invite')) {
            $this->extendPasswordBrokerExpiry();
        }

        return $next($request);
    }

    protected function isPasswordResetRoute(?string $routeName): bool
    {
        if ($routeName === null) {
            return false;
        }

        if ($routeName === 'password.reset') {
            return true;
        }

        return (bool) preg_match('/^filament\.[^.]+\.auth\.password-reset\.reset$/', $routeName);
    }

    protected function extendPasswordBrokerExpiry(): void
    {
        $brokerName = $this->passwordBrokerName();

        config([
            "auth.passwords.{$brokerName}.expire" => config('filament-invite.expire'),
        ]);

        $this->forgetCachedPasswordBrokers();
    }

    protected function passwordBrokerName(): string
    {
        try {
            return Filament::getAuthPasswordBroker() ?? config('auth.defaults.passwords');
        } catch (NoDefaultPanelSetException) {
            return config('auth.defaults.passwords');
        }
    }

    protected function forgetCachedPasswordBrokers(): void
    {
        $manager = Password::getFacadeRoot();

        $brokers = new ReflectionProperty($manager, 'brokers');
        $brokers->setValue($manager, []);
    }
}
