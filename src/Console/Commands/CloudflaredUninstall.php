<?php

namespace Aerni\Cloudflared\Console\Commands;

use Illuminate\Console\Command;
use Aerni\Cloudflared\TunnelConfig;
use Aerni\Cloudflared\ProjectConfig;
use Aerni\Cloudflared\Facades\Cloudflared;
use Aerni\Cloudflared\Concerns\InteractsWithHerd;
use Aerni\Cloudflared\Concerns\InteractsWithTunnel;

class CloudflaredUninstall extends Command
{
    use InteractsWithHerd, InteractsWithTunnel;

    protected $signature = 'cloudflared:uninstall';

    protected $description = 'Delete the Cloudflare Tunnel of this project.';

    protected ProjectConfig $projectConfig;

    public function handle(): void
    {
        if (! Cloudflared::isInstalled()) {
            $this->fail("Missing file <info>.cloudflared.yaml</info>. There is nothing to uninstall.");
        }

        $this->projectConfig = Cloudflared::projectConfig();

        $this->deleteCloudflaredTunnel($this->projectConfig->hostname);
        $this->deleteHerdLink($this->projectConfig->hostname);
        $this->deleteProjectConfigs();
        // Optionally: Delete DNS record. This requires a Cloudflare API token.
    }

    protected function deleteProjectConfigs(): void
    {
        Cloudflared::tunnelConfig()->delete();

        $this->projectConfig->delete();

        info("<info>[✔]</info> Deleted tunnel configs");
    }
}
