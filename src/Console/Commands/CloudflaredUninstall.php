<?php

namespace Aerni\Cloudflared\Console\Commands;

use Illuminate\Console\Command;
use Aerni\Cloudflared\Facades\Cloudflared;
use Aerni\Cloudflared\Concerns\InteractsWithHerd;
use Aerni\Cloudflared\Concerns\InteractsWithTunnel;
use Aerni\Cloudflared\TunnelConfig;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class CloudflaredUninstall extends Command
{
    use InteractsWithHerd, InteractsWithTunnel;

    protected $signature = 'cloudflared:uninstall';

    protected $description = 'Delete the Cloudflare Tunnel of this project.';

    protected TunnelConfig $tunnelConfig;

    public function handle(): void
    {
        if (! Cloudflared::isInstalled()) {
            $this->fail("Missing file <info>.cloudflared.yaml</info>. There is nothing to uninstall.");
        }

        $this->tunnelConfig = Cloudflared::tunnelConfig();

        $confirmed = confirm(
            label: "Are you sure you want to uninstall the {$this->tunnelConfig->hostname()} tunnel?",
            default: false,
            hint: 'Deletes the cloudflared tunnel, Herd link, and all associated configs.'
        );

        if (! $confirmed) {
            error(' ⚠ Cancelled.');
            return;
        }

        $this->deleteCloudflaredTunnel($this->tunnelConfig->hostname());
        $this->deleteHerdLink($this->tunnelConfig->hostname());
        $this->deleteProjectConfigs();
        // Optionally: Delete DNS record. This requires a Cloudflare API token.
    }

    protected function deleteProjectConfigs(): void
    {
        $this->tunnelConfig->delete();
        $this->tunnelConfig->projectConfig->delete();

        info("<info>[✔]</info> Deleted tunnel configs");
    }
}
