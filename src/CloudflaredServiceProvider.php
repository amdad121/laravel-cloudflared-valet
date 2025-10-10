<?php

namespace Aerni\Cloudflared;

use Illuminate\Support\ServiceProvider;
use Aerni\Cloudflared\Facades\Cloudflared;
use Aerni\Cloudflared\Console\Commands\CloudflaredRun;
use Aerni\Cloudflared\Console\Commands\CloudflaredInstall;
use Aerni\Cloudflared\Console\Commands\CloudflaredUninstall;

class CloudflaredServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! Cloudflared::isInstalled()) {
            return;
        }

        if (request()->host() !== Cloudflared::tunnelConfig()->hostname()) {
            return;
        }

        config()->set('app.url', Cloudflared::tunnelConfig()->url());
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CloudflaredInstall::class,
                CloudflaredRun::class,
                CloudflaredUninstall::class,
            ]);
        }
    }
}
