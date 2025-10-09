<?php

namespace Aerni\Cloudflared\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Aerni\Cloudflared\Concerns\HasProjectConfig;
use Aerni\Cloudflared\Concerns\InteractsWithHerd;
use Aerni\Cloudflared\Concerns\InteractsWithTunnel;
use Aerni\Cloudflared\ProjectConfig;

class CloudflaredUninstall extends Command
{
    use HasProjectConfig, InteractsWithHerd, InteractsWithTunnel;

    protected $signature = 'cloudflared:uninstall';

    protected $description = 'Delete the Cloudflare Tunnel of this project.';

    public function __construct(protected ProjectConfig $config)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        if (! File::exists($this->config->path())) {
            $this->fail("Missing file <info>.cloudflared.yaml</info>. There is nothing to uninstall.");
        }

        $this->deleteCloudflaredTunnel($this->config->hostname());
        $this->deleteHerdLink($this->config->hostname());
        $this->deleteProjectFiles();
        // Optionally: Delete DNS record. This requires a Cloudflare API token.
    }

    protected function deleteProjectFiles(): void
    {
        File::delete($this->config->path());
        File::delete($this->config->tunnelConfigPath());

        info("<info>[✔]</info> Deleted project files");
    }
}
