<?php

namespace Tapp\FilamentInvite\Concerns;

use Filament\Facades\Filament;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Tapp\FilamentInvite\Notifications\SetPassword;

trait InvitesUsers
{
    protected function sendInviteToUser(Model $user): void
    {
        $broker = Password::broker(Filament::getAuthPasswordBroker());

        if (! $broker instanceof PasswordBroker) {
            throw new RuntimeException('Unexpected password broker implementation.');
        }

        $token = $broker->createToken($user);

        if (method_exists($user, 'sendPasswordSetNotification')) {
            $user->sendPasswordSetNotification($token);

            return;
        }

        Notification::send($user, new SetPassword($token));
    }

    protected function canInviteUser(Model $user): bool
    {
        if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail()) {
            return false;
        }

        $authenticatedUser = auth()->user();

        if ($authenticatedUser === null) {
            return false;
        }

        return $authenticatedUser->can('update', $user);
    }
}
