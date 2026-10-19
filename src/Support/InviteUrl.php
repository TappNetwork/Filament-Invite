<?php

namespace Tapp\FilamentInvite\Support;

class InviteUrl
{
    /**
     * Build the set-password invite URL for the given notifiable and token.
     *
     * Shared by the SetPassword notification and copy-link action so both
     * paths produce the same URL (including invite=1 for extended expiry).
     */
    public static function for(mixed $notifiable, string $token): string
    {
        if (is_object($notifiable) && method_exists($notifiable, 'getResetPasswordUrl')) {
            return $notifiable->getResetPasswordUrl($token);
        }

        $email = '';

        if (is_string($notifiable)) {
            $email = $notifiable;
        }

        if (is_object($notifiable) && isset($notifiable->email)) {
            $email = $notifiable->email;
        }

        return url(config('app.url') . route(config('filament-invite.routes.reset'), [
            'token' => $token,
            'email' => $email,
            'invite' => true,
        ], false));
    }
}
