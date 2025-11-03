<?php

namespace Aerni\Cloudflared\Concerns;

use Aerni\Cloudflared\Facades\Cloudflared;
use Aerni\Cloudflared\ProjectConfig;
use Illuminate\Support\Facades\Process;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

trait InteractsWithTunnel
{
    protected function verifyCloudflaredFoundInPath(): void
    {
        if (Process::run('cloudflared --version')->failed()) {
            $this->fail('cloudflared not found in PATH.');
        }
    }

    protected function tunnelExists(string $name): bool
    {
        return spin(
            callback: fn () => Process::run("cloudflared tunnel info {$name}")->successful(),
            message: "Verifying that there is no existing tunnel for {$name}."
        );
    }

    protected function createTunnel(string $name): ProjectConfig
    {
        $result = spin(
            callback: fn () => Process::run("cloudflared tunnel create {$name}"),
            message: 'Creating tunnel'
        );

        $result->throw();

        if (! preg_match('/Created tunnel .+ with id ([a-f0-9\-]+)/', $result->output(), $tunnelMatch)) {
            $this->fail('Unable to extract the tunnel ID from cloudflared output.');
        }

        info(' ✔ Created tunnel.');

        return Cloudflared::makeProjectConfig(tunnel: $tunnelMatch[1], hostname: $name);
    }

    protected function handleExistingTunnel(string $name): ProjectConfig
    {
        warning(" ⚠ A tunnel for {$name} already exists.");

        $selection = select(
            label: 'How do you want to proceed?',
            options: ['Choose a different hostname', 'Delete existing tunnel and continue']
        );

        if ($selection === 'Choose a different hostname') {
            // TODO: This method only exists in the install command. Should we extract it? Or how should we handle this?
            $hostname = $this->askForHostname();

            return $this->tunnelExists($hostname)
                ? $this->handleExistingTunnel($hostname)
                : $this->createTunnel($hostname);
        }

        $this->deleteTunnel($name);

        return $this->createTunnel($name);
    }

    protected function deleteTunnel(string $name): void
    {
        $result = spin(
            callback: fn () => Process::run("cloudflared tunnel delete {$name}"),
            message: "Deleting tunnel: {$name}"
        );

        if ($result->seeInErrorOutput("there should only be 1 non-deleted Tunnel named {$name}")) {
            warning(" ⚠ Can't delete tunnel {$name} as it doesn't exist.");

            return;
        }

        $result->throw();

        info(' ✔ Deleted tunnel.');
    }

    protected function createDnsRecord(string $tunnelId, string $hostname, bool $overwrite = false): void
    {
        $command = $overwrite
            ? "cloudflared tunnel route dns --overwrite-dns {$tunnelId} {$hostname}"
            : "cloudflared tunnel route dns {$tunnelId} {$hostname}";

        $result = spin(
            callback: fn () => Process::run($command),
            message: "Creating DNS record: {$hostname}"
        );

        if ($result->seeInErrorOutput('Failed to add route: code: 1003')) {
            $this->handleExistingDnsRecord($tunnelId, $hostname);

            return;
        }

        $result->throw();

        info(" ✔ Created DNS record: {$hostname}");
    }

    protected function handleExistingDnsRecord(string $tunnelId, string $hostname): void
    {
        warning(" ⚠ A DNS record for {$hostname} already exists.");

        $selection = select(
            label: 'How do you want to proceed?',
            options: ['Choose a different hostname', 'Overwrite existing record and continue', 'Abort and delete the tunnel']
        );

        if ($selection === 'Choose a different hostname') {
            $this->deleteTunnel($hostname);
            // TODO: This only works in the context of the install command. How can we make this more generic?
            $this->handle();

            return;
        }

        if ($selection === 'Overwrite existing record and continue') {
            $this->createDnsRecord(tunnelId: $tunnelId, hostname: $hostname, overwrite: true);

            return;
        }

        $this->deleteTunnel($hostname);
        exit(0);
    }
}
