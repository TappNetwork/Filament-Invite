<?php

namespace Tapp\FilamentInvite\Tests\Fixtures\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class InviteTestUser extends Authenticatable implements CanResetPasswordContract, MustVerifyEmail
{
    use CanResetPassword;
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];

    public bool $canUpdateOthers = true;

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill(['email_verified_at' => now()])->save();
    }

    public function sendEmailVerificationNotification(): void
    {
        //
    }

    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    public function can($abilities, $arguments = []): bool
    {
        return $abilities === 'update' && $this->canUpdateOthers;
    }
}
