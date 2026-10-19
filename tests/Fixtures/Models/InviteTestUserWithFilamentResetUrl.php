<?php

namespace Tapp\FilamentInvite\Tests\Fixtures\Models;

use Illuminate\Support\Facades\URL;

class InviteTestUserWithFilamentResetUrl extends InviteTestUser
{
    public function getResetPasswordUrl(string $token, array $parameters = []): string
    {
        return URL::signedRoute(
            'filament.admin.auth.password-reset.reset',
            [
                'email' => $this->email,
                'token' => $token,
                ...$parameters,
            ],
        );
    }
}
