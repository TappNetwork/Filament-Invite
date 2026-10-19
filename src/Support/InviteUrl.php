<?php

namespace Tapp\FilamentInvite\Support;

class InviteUrl
{
    public static function for(mixed $notifiable, string $token): string
    {
        if (is_object($notifiable) && method_exists($notifiable, 'getResetPasswordUrl')) {
            return $notifiable->getResetPasswordUrl($token, ['invite' => 1]);
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
            'invite' => 1,
        ], false));
    }
}
