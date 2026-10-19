<?php

namespace Tapp\FilamentInvite\Tests\Fixtures\Pages;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Tapp\FilamentInvite\Actions\CopyInviteLinkAction;

class EditInviteTestUser extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public Model $record;

    public function mount(Model $record): void
    {
        $this->record = $record;
    }

    public function copy_invite_linkAction(): CopyInviteLinkAction
    {
        return CopyInviteLinkAction::make()
            ->record($this->record);
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
