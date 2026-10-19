<?php

namespace Tapp\FilamentInvite\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Tapp\FilamentInvite\Concerns\InvitesUsers;

class CopyInviteLinkAction extends Action
{
    use InvitesUsers;

    public static function getDefaultName(): ?string
    {
        return 'copy_invite_link';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-invite::invite.copy_link.label'));

        $this->modalHeading(__('filament-invite::invite.copy_link.modal_heading'));

        $this->modalDescription(__('filament-invite::invite.copy_link.modal_description'));

        $this->icon('heroicon-m-clipboard-document');

        $this->hidden(function (Model $user) {
            if (! $user instanceof MustVerifyEmail) {
                return true;
            }

            return $user->hasVerifiedEmail() || auth()->user()->can('update', $user) === false;
        });

        $this->fillForm(function (Model $user): array {
            return [
                'invite_url' => $this->createInviteLink($user),
            ];
        });

        $this->schema([
            TextInput::make('invite_url')
                ->label(__('filament-invite::invite.copy_link.field_label'))
                ->readOnly()
                ->copyable()
                ->dehydrated(false),
        ]);

        $this->modalSubmitAction(false);

        $this->modalCancelActionLabel(__('filament-invite::invite.copy_link.close'));
    }
}
