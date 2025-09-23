<?php

namespace Aerni\Cloudflared\Console\Commands;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\error;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class CloudflaredUninstall extends Command
{
    protected $signature = 'cloudflared:uninstall';

    protected $description = 'Uninstall a Cloudflare Tunnel.';

    protected string $hostname;

    public function handle(): void
    {
        $configPath = base_path('.cloudflared.yaml');

        if (! file_exists($configPath)) {
            error('Unable to find .cloudflared.yaml config.');
            return;
        }

        $this->hostname = Arr::get(Yaml::parseFile($configPath), 'hostname');

        if (! $this->hostname) {
            error('Hostname not found in .cloudflared.yaml file.');
            return;
        }

        $result = spin(
            callback: fn () => Process::run("cloudflared tunnel delete {$this->hostname}"),
            message: 'Deleting tunnel …'
        );

        if ($result->failed()) {
            throw new \RuntimeException($result->errorOutput());
        }

        File::delete($configPath);

        info("<info>[✔]</info> Deleted tunnel.");

        $this->deleteHerdLink();

        // Optionally: Can we also delete the associated DNS records
    }

    protected function deleteHerdLink()
    {
        $result = spin(
            callback: fn () => Process::run("herd unlink {$this->hostname}"),
            message: 'Deleting Laravel Herd link …'
        );

        if ($result->failed()) {
            throw new \RuntimeException($result->errorOutput());
        }

        info("<info>[✔]</info> Deleted Laravel Herd link.");
    }
}
