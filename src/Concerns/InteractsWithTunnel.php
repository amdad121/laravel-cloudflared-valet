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
            callback: function () use ($name) {
                Process::run("cloudflared tunnel delete {$name}")->throw();
                // Fix this: We need to delete the tunnel configs.
                // This can only be deleted if there is an existing .cloudflared.yaml file. Else, we don't know the id of the tunnel config to delete.
                // Unless the error message from the create tunnel command contains the id of the existing tunnel?
                // File::delete(ProjectConfig::tunnelCredentialsPath());
                // File::delete($this->tunnelConfigPath());
            },
            message: "Deleting tunnel: {$name}"
        );

        info("<info>[✔]</info> Deleted tunnel: {$name}");
    }
}
