<?php

namespace Aerni\Cloudflared;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class CloudflaredServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        if (! $this->isRunningViteServer()) {
            return;
        }

        if (! $this->isCloudflaredRequest()) {
            return;
        }

        config()->set('app.url', env('CLOUDFLARED_APP_URL'));
    }

    protected function isRunningViteServer(): bool
    {
        return File::exists(public_path('hot'));
    }

    protected function isCloudflaredRequest(): bool
    {
        return request()->host() === parse_url(env('CLOUDFLARED_APP_URL'), PHP_URL_HOST);
    }
}
