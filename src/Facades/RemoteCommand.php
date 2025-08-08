<?php

namespace LaravelRemoteCommands\Facades;

use Illuminate\Support\Facades\Facade;

class RemoteCommand extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'remote-command';
    }
}
