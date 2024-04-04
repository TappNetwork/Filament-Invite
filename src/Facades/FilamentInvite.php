<?php

namespace Tapp\FilamentInvite\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Tapp\FilamentInvite\FilamentInvite
 */
class FilamentInvite extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Tapp\FilamentInvite\FilamentInvite::class;
    }
}
