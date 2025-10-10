<?php

namespace Aerni\Cloudflared;

use Illuminate\Support\ServiceProvider;
use Aerni\Cloudflared\Facades\Cloudflared;
use Aerni\Cloudflared\Console\Commands\CloudflaredStart;
use Aerni\Cloudflared\Console\Commands\CloudflaredInstall;
use Aerni\Cloudflared\Console\Commands\CloudflaredUninstall;

class CloudflaredServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! Cloudflared::isInstalled()) {
            return;
        }

        $projectConfig = ProjectConfig::load();
        $tunnelConfig = TunnelConfig::make($projectConfig);

        if (request()->host() === $projectConfig->hostname) {
            config()->set('app.url', $tunnelConfig->url());
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CloudflaredInstall::class,
                CloudflaredStart::class,
                CloudflaredUninstall::class,
            ]);
        }
    }
}
