<?php

namespace Aerni\Cloudflared\Console\Commands;

use Aerni\Cloudflared\Concerns\InteractsWithHerd;
use Aerni\Cloudflared\Concerns\InteractsWithTunnel;
use Aerni\Cloudflared\Concerns\ManagesProject;
use Aerni\Cloudflared\Facades\Cloudflared;
use Aerni\Cloudflared\ProjectConfig;
use Illuminate\Console\Command;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class CloudflaredInstall extends Command
{
    use InteractsWithHerd, InteractsWithTunnel, ManagesProject;

    protected $signature = 'cloudflared:install';

    protected $description = 'Create a Cloudflare Tunnel for this project.';

    protected ProjectConfig $projectConfig;

    public function handle()
    {
        $this->verifyCloudflaredFoundInPath();
        $this->verifyHerdFoundInPath();

        if (Cloudflared::isInstalled()) {
            $this->handleExistingInstallation();
        }

        $hostname = $this->askForHostname();

        $createViteDnsRecord = confirm(
            label: 'Do you want to create a DNS record for Vite?',
            hint: 'Required for the vite-plugin-laravel-cloudflared.',
        );

        $this->projectConfig = $this->tunnelExists($hostname)
            ? $this->handleExistingTunnel($hostname)
            : $this->createTunnel($hostname);

        $this->createAppDnsRecord();

        if ($createViteDnsRecord) {
            $this->createViteDnsRecord();
        }

        $this->createHerdLink($this->projectConfig->hostname);
        $this->saveProjectConfig($this->projectConfig);
    }

    protected function handleExistingInstallation(): void
    {
        $tunnelConfig = Cloudflared::tunnelConfig();

        warning(" ⚠ There is an existing tunnel for this project (hostname: {$tunnelConfig->hostname()}).");

        $selection = select(
            label: 'How do you want to proceed?',
            options: ['Abort', 'Delete existing tunnel and create a new one']
        );

        if ($selection === 'Abort') {
            error(' ⚠ Installation aborted.');
            exit(0);
        }

        $this->deleteTunnel($tunnelConfig->hostname());
        $this->deleteHerdLink($tunnelConfig->hostname());
        $this->deleteProject($tunnelConfig);
    }

    protected function createAppDnsRecord(): void
    {
        $this->createDnsRecord($this->projectConfig->tunnel, $this->projectConfig->hostname);
    }

    protected function createViteDnsRecord(): void
    {
        $this->createDnsRecord($this->projectConfig->tunnel, "vite-{$this->projectConfig->hostname}");
    }

    protected function askForHostname(): string
    {
        return text(
            label: 'The hostname you want to connect to this tunnel.',
            placeholder: "{$this->herdSiteName()}.domain.com",
            hint: "Use a subdomain that matches the name of this site (e.g., {$this->herdSiteName()}.domain.com).",
            validate: fn (string $value) => match (true) {
                empty($value) => 'The hostname field is required.',
                count(array_filter(explode('.', $value))) < 3 => "The hostname must be a subdomain (e.g., {$this->herdSiteName()}.domain.com).",
                default => null,
            },
        );
    }

    // TODO: Handle exit handlers in case users abort the command midway.
    // - delete tunnels that were created.
    // - delete .cloudflared.yaml file.
}
