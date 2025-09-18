<?php

namespace Aerni\Cloudflared;

use Illuminate\Support\ServiceProvider;

class CloudflaredServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        if (request()->root() !== env('CLOUDFLARED_APP_URL')) {
            return;
        }

        config()->set('app.url', env('CLOUDFLARED_APP_URL'));
    }
}
