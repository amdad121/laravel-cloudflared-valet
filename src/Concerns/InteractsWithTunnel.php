<?php

namespace Aerni\Cloudflared\Concerns;

use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use Illuminate\Support\Facades\Process;

trait InteractsWithTunnel
{
    protected function deleteCloudflaredTunnel(string $name): void
    {
        spin(
            callback: fn () => Process::run("cloudflared tunnel delete {$name}")->throw(),
            message: "Deleting tunnel: {$name}"
        );

        info("<info>[✔]</info> Deleted tunnel: {$name}");
    }
}
