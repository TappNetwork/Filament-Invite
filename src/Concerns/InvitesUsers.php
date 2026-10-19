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
use Tapp\FilamentInvite\Support\InviteUrl;

trait InvitesUsers
{
    protected function createInviteToken(Model $user): string
    {
        $broker = Password::broker(Filament::getAuthPasswordBroker());

        if (! $broker instanceof PasswordBroker) {
            throw new RuntimeException('Unexpected password broker implementation.');
        }

        return $broker->createToken($user);
    }

    protected function createInviteLink(Model $user): string
    {
        if (! $this->canInviteUser($user)) {
            throw new RuntimeException('User cannot be invited.');
        }

        $token = $this->createInviteToken($user);

        return InviteUrl::for($user, $token);
    }

    protected function sendInviteToUser(Model $user): void
    {
        $token = $this->createInviteToken($user);

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
