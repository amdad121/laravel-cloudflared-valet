<?php

namespace Aerni\Cloudflared\Console\Commands;

use Aerni\Cloudflared\Concerns\InteractsWithHerd;
use Aerni\Cloudflared\Concerns\InteractsWithTunnel;
use Aerni\Cloudflared\Concerns\ManagesProject;
use Aerni\Cloudflared\Facades\Cloudflared;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;

class CloudflaredUninstall extends Command
{
    use InteractsWithHerd, InteractsWithTunnel, ManagesProject;

    protected $signature = 'cloudflared:uninstall';

    protected $description = 'Delete the Cloudflare Tunnel of this project.';

    public function handle()
    {
        $this->verifyCloudflaredFoundInPath();
        $this->verifyHerdFoundInPath();

        if (! Cloudflared::isInstalled()) {
            $this->fail('Missing project file: .cloudflared.yaml');
        }

        $tunnelConfig = Cloudflared::tunnelConfig();

        $confirmed = confirm(
            label: "Are you sure you want to delete the {$tunnelConfig->name()} tunnel?",
            hint: 'Deletes the cloudflared tunnel, Herd link, and all associated configs.',
            default: false,
        );

        if (! $confirmed) {
            error(' ⚠ Uninstallation aborted.');

            return self::SUCCESS;
        }

        $this->deleteTunnel($tunnelConfig->name());
        $this->deleteHerdLink($tunnelConfig->hostname());
        $this->deleteProject($tunnelConfig);

        // Optionally: Delete DNS record. This requires a Cloudflare API token.
    }
}
