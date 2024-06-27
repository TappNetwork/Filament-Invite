<?php

namespace Tapp\FilamentInvite\Actions;

use Filament\Actions\Action;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tapp\FilamentInvite\Notifications\SetPassword;

class InviteAction extends Action
{
    use CanCustomizeProcess;

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
            /** @var \Illuminate\Contracts\Auth\MustVerifyEmail $user */
            return $user->hasVerifiedEmail() || auth()->user()->can('update', $user) === false;
        });

        $this->action(function (): void {
            $result = $this->process(static function (Model $user) {
                $token = Password::broker()->createToken($user);

                // Use the method if the developer has specified one
                if (method_exists($user, 'sendPasswordResetNotification')) {
                    $user->sendPasswordSetNotification($token);
                } else {
                    Notification::send($user, new SetPassword($token));
                }
            });

            $this->success();
        });
    }
}
