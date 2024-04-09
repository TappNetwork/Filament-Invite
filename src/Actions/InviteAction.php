<?php

namespace Tapp\FilamentInvite\Actions;

use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tapp\FilamentInvite\Notifications\SetPassword;

class InviteAction extends Action
{
    use CanCustomizeProcess;

    protected ?Closure $mutateRecordDataUsing = null;

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

        $this->hidden(fn (Model $user) => $user->email_verified_at);

        $this->action(function (): void {
            $result = $this->process(static function (Model $user) {
                $token = Password::broker()->createToken($user);

                // Use the method if the developer has specified one
                if (method_exists($user, 'sendPasswordSetNotification')) {
                    $user->sendPasswordSetNotification($token);
                } else {
                    Notification::send($user, new SetPassword($token));
                }
            });

            $this->success();
        });
    }
}
