<?php

namespace Tapp\FilamentInvite\Http;

use Closure;

class InviteMiddleware
{
    public function handle($request, Closure $next)
    {
        if ($request->route()->getName() != 'password.reset') {
            return $next($request);
        }

        if ($request->has('invite')) {
            session()->put('invite', true);

            return $next($request);
        }

        // TODO setup and publish config

        // Update config if the expire value is specified
        if (session()->has('invite')) {
            config(['auth.passwords.users.expire' => config('filament-invite.expire')]);
        }

        return $next($request);
    }
}
