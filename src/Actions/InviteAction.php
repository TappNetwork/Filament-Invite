<?php

namespace Tapp\FilamentInvite\Actions;

use Filament\Actions\Action;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Tapp\FilamentInvite\Concerns\InvitesUsers;

class InviteAction extends Action
{
    use CanCustomizeProcess;
    use InvitesUsers;

    public static function getDefaultName(): ?string
    {
        return 'invite';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Invite'));

        $this->modalHeading(__('Send Invite Email'));

        $this->requiresConfirmation(true);

        $this->icon('heroicon-m-envelope');

        $this->hidden(function (Model $user) {
            /** @var MustVerifyEmail $user */
            return $user->hasVerifiedEmail() || auth()->user()->can('update', $user) === false;
        });

        $this->action(function (): void {
            $this->process(function (Model $user): void {
                $this->sendInviteToUser($user);
            });

            $this->success();
        });
    }
}
