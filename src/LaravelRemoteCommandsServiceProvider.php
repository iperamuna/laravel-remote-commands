<?php

namespace LaravelRemoteCommands;

use Illuminate\Support\ServiceProvider;
use LaravelRemoteCommands\Services\LaravelRemoteCommandService;

class LaravelRemoteCommandsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/remote-commands.php' => config_path('remote-commands.php'),
        ], 'remote-commands-config');
    }

    public function register(): void
    {

        $this->app->singleton('remote-command', function () {
            return new LaravelRemoteCommandService();
        });
    }
}
