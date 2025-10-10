<?php

namespace Aerni\Cloudflared\Concerns;

use Illuminate\Support\Facades\Process;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;

trait InteractsWithTunnel
{
    protected function deleteCloudflaredTunnel(string $name): void
    {
        spin(
            callback: fn () => Process::run("cloudflared tunnel delete {$name}")->throw(),
            message: "Deleting tunnel: {$name}"
        );

        info(' ✔ Deleted tunnel.');
    }
}
