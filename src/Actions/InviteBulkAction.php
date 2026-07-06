<?php

namespace Tapp\FilamentInvite\Actions;

use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Tapp\FilamentInvite\Concerns\InvitesUsers;

class InviteBulkAction extends BulkAction
{
    use InvitesUsers;

    public static function getDefaultName(): ?string
    {
        return 'invite_users';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-invite::invite.bulk.label'));

        $this->icon('heroicon-m-envelope');

        $this->requiresConfirmation();

        $this->modalHeading(__('filament-invite::invite.bulk.modal_heading'));

        $this->action(function (Collection $records): void {
            $invitedCount = 0;
            $skippedCount = 0;

            foreach ($records as $user) {
                if (! $this->canInviteUser($user)) {
                    $skippedCount++;

                    continue;
                }

                $this->sendInviteToUser($user);
                $invitedCount++;
            }

            if ($invitedCount === 0) {
                Notification::make()
                    ->title(__('filament-invite::invite.bulk.notifications.none.title'))
                    ->body(__('filament-invite::invite.bulk.notifications.none.body'))
                    ->warning()
                    ->send();

                return;
            }

            $body = trans_choice(
                'filament-invite::invite.bulk.notifications.sent.body',
                $invitedCount,
                ['count' => $invitedCount],
            );

            if ($skippedCount > 0) {
                $body .= ' ' . trans_choice(
                    'filament-invite::invite.bulk.notifications.skipped.body',
                    $skippedCount,
                    ['count' => $skippedCount],
                );
            }

            Notification::make()
                ->title(__('filament-invite::invite.bulk.notifications.sent.title'))
                ->body($body)
                ->success()
                ->send();
        });

        $this->deselectRecordsAfterCompletion();
    }
}
