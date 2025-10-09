<?php

namespace Aerni\Cloudflared;

use Aerni\Cloudflared\ProjectConfig;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Aerni\Cloudflared\CloudflaredConfig;
use Aerni\Cloudflared\Console\Commands\CloudflaredStart;
use Aerni\Cloudflared\Console\Commands\CloudflaredInstall;
use Aerni\Cloudflared\Console\Commands\CloudflaredUninstall;

class CloudflaredServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!File::exists(CloudflaredConfig::path())) {
            return;
        }

        $this->app->singleton(ProjectConfig::class, fn () => new ProjectConfig(CloudflaredConfig::load()));

        $projectConfig = app(ProjectConfig::class);

        if (request()->host() === $projectConfig->hostname()) {
            config()->set('app.url', $projectConfig->url());
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
