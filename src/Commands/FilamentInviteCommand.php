<?php

namespace Tapp\FilamentInvite\Commands;

use Illuminate\Console\Command;

class FilamentInviteCommand extends Command
{
    public $signature = 'filament-invite';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
