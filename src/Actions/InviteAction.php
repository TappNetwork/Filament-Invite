<?php

namespace Tapp\FilamentInvite\Actions;

use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Tables\Actions\Action;

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

        $this->modalHeading(fn (): string => __('Invite Heading', ['label' => $this->getRecordTitle()]));

        $this->modalSubmitActionLabel(__('Invite Submit'));

        $this->successNotificationTitle(__('Invite Success'));

        $this->icon('heroicon-m-envelope');

        $this->action(function (): void {
            $this->success();
        });
    }
}
