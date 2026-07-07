<?php

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tapp\FilamentInvite\Actions\InviteBulkAction;
use Tapp\FilamentInvite\Concerns\InvitesUsers;

it('registers invite bulk action with the expected default name', function (): void {
    expect(InviteBulkAction::getDefaultName())->toBe('invite_users');
});

it('can determine which users are eligible for invites', function (): void {
    $inviter = new class extends Authenticatable
    {
        use Notifiable;

        public function can($abilities, $arguments = []): bool
        {
            return $abilities === 'update';
        }
    };

    $unverifiedUser = new class extends Authenticatable implements MustVerifyEmail
    {
        use Notifiable;

        protected $table = 'users';

        public function hasVerifiedEmail(): bool
        {
            return false;
        }
    };

    $verifiedUser = new class extends Authenticatable implements MustVerifyEmail
    {
        use Notifiable;

        protected $table = 'users';

        public function hasVerifiedEmail(): bool
        {
            return true;
        }
    };

    $action = new class extends Model
    {
        use InvitesUsers;

        public function exposeCanInviteUser(Model $user): bool
        {
            return $this->canInviteUser($user);
        }
    };

    $this->actingAs($inviter);

    expect($action->exposeCanInviteUser($unverifiedUser))->toBeTrue()
        ->and($action->exposeCanInviteUser($verifiedUser))->toBeFalse();
});
