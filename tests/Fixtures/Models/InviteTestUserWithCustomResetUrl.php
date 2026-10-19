<?php

namespace Tapp\FilamentInvite\Tests\Fixtures\Models;

use Illuminate\Support\Facades\URL;

class InviteTestUserWithCustomResetUrl extends InviteTestUser
{
    public function getResetPasswordUrl(string $token, array $parameters = []): string
    {
        return URL::to('/custom-reset/' . $token . '?email=' . urlencode($this->email));
    }
}
