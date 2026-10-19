<?php

namespace Tapp\FilamentInvite\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
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

        $this->hidden(fn (Model $user): bool => ! $this->canInviteUser($user));

        $this->fillForm(fn (): array => [
            'invite_url' => null,
        ]);

        $this->schema([
            TextInput::make('invite_url')
                ->label(__('filament-invite::invite.copy_link.field_label'))
                ->placeholder(__('filament-invite::invite.copy_link.field_placeholder'))
                ->readOnly()
                ->copyable()
                ->dehydrated(false)
                ->visible(fn (?string $state): bool => filled($state)),
        ]);

        $this->modalSubmitActionLabel(__('filament-invite::invite.copy_link.generate'));

        $this->modalCancelActionLabel(__('filament-invite::invite.copy_link.close'));

        $this->action(function (Model $user, Action $action, Schema $schema): void {
            $schema->fill([
                'invite_url' => $this->createInviteLink($user),
            ]);

            $action->halt();
        });
    }
}
