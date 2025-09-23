<?php

namespace Aerni\Cloudflared\Console\Commands;

use Illuminate\Process\Pool;
use Illuminate\Console\Command;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;
use function Laravel\Prompts\form;
use function Laravel\Prompts\spin;
use Illuminate\Support\Facades\File;
use function Laravel\Prompts\confirm;
use Illuminate\Support\Facades\Process;
use Aerni\Cloudflared\Console\Commands\CloudflaredUninstall;

class CloudflaredInstall extends Command
{
    protected $signature = 'cloudflared:install {--force : Overwrite any existing CNAME records.}';

    protected $description = 'Create a Cloudflare Tunnel for this project.';

    protected string $herdSite;
    protected string $hostname;
    protected string $subdomain;
    protected string $tunnelId;
    protected string $credentialsFilePath;

    public function handle(): void
    {
        // If there is an existing config file, we shouldn't create a new tunnel
        // But we should ensure that the tunnel exists
        // And if it exists, we should ensure that the CNAME records exist.
        // If there is no tunnel (no config found in .cloudflared directoy), we should create a new tunnel
        // and overwrite the .cloudflared.yaml config.

        // if (File::exists('.cloudflared.yaml')) {
        //     warning('Cloudflared was already installed for this project.');
        //     return;
        // }

        $this->herdSite = basename(base_path());

        $this->hostname = text(
            label: 'What hostname do you want to associate with this tunnel?',
            placeholder: "{$this->herdSite}.domain.com",
            hint: 'It is recommended to match the subdomain to your Laravel Herd site.',
            validate: fn (string $value) => match (true) {
                empty($value) => 'The hostname field is required.',
                count(array_filter(explode('.', $value))) < 3 => "The hostname must include a subdomain (e.g., {$this->herdSite}.domain.com).",
                default => null,
            },
        );

        $this->createCloudflaredTunnel();
        $this->createCloudflaredFile();
        $this->createAppDnsRecord();
        $this->createViteDnsRecord();
        $this->createHerdLink();
    }

    protected function createCloudflaredTunnel(): void
    {
        $result = spin(
            callback: fn () => Process::run("cloudflared tunnel create {$this->hostname}"),
            message: 'Creating tunnel …'
        );

        if ($result->failed()) {
            throw new \RuntimeException($result->errorOutput());
        }

        if (! preg_match('/([^\s]+\.json)(?=$|\s|\.)/', $result->output(), $credentialsPathMatch)) {
            throw new \RuntimeException("Unable to extract the credentials file path.");
        }

        if (! preg_match('/Created tunnel .+ with id ([a-f0-9\-]+)/', $result->output(), $tunnelMatch)) {
            throw new \RuntimeException("Unable to extract the tunnel ID.");
        }

        $this->tunnelId = $tunnelMatch[1];
        $this->credentialsFilePath = $credentialsPathMatch[1];

        info("<info>[✔]</info> Created tunnel.");
    }

    protected function createCloudflaredFile(): void
    {
        $config = <<<YAML
tunnel: {$this->tunnelId}
credentials-file: {$this->credentialsFilePath}
hostname: {$this->hostname}
YAML;

        File::put('.cloudflared.yaml', $config);
    }

    protected function createAppDnsRecord(bool $force = false): void
    {
        $command = $this->option('force') ?? $force
            ? "cloudflared tunnel route dns --overwrite-dns {$this->hostname} {$this->hostname}"
            : "cloudflared tunnel route dns {$this->hostname} {$this->hostname}";

        $result = spin(
            callback: fn () => Process::run($command),
            message: "Creating DNS record: {$this->hostname} …"
        );

        if ($result->seeInErrorOutput('Failed to add route: code: 1003')) {
            $confirmed = confirm(
                label: "A DNS record for {$this->hostname} already exists. Overwrite?",
                yes: 'Yes, overwrite the record.',
                no: 'No, abort and delete the tunnel.',
            );

            // If this is true, we need to stop command execution.
            // We should not continue to the ViteDnsRecord method or the Herd link method.
            if (!$confirmed) {
                $this->callSilently(CloudflaredUninstall::class);
                $this->fail('The tunnel was deleted.');
            }

            $this->createAppDnsRecord(true);

            return;
        }

        if ($result->failed()) {
            throw new \RuntimeException($result->errorOutput());
        }

        info("<info>[✔]</info> Created DNS record: {$this->hostname}");
    }

    protected function createViteDnsRecord(bool $force = false): void
    {
        $command = $this->option('force') ?? $force
            ? "cloudflared tunnel route dns --overwrite-dns {$this->hostname} vite-{$this->hostname}"
            : "cloudflared tunnel route dns {$this->hostname} vite-{$this->hostname}";

        $result = spin(
            callback: fn () => Process::run($command),
            message: "Creating DNS record: vite-{$this->hostname} …"
        );

        if ($result->seeInErrorOutput('Failed to add route: code: 1003')) {
            $confirmed = confirm(
                label: "A DNS record for vite-{$this->hostname} already exists. Overwrite?",
                yes: 'Yes, overwrite the record.',
                no: 'No, abort and delete the tunnel.',
            );

            if (!$confirmed) {
                $this->callSilently(CloudflaredUninstall::class);
                $this->fail('The tunnel was deleted.');
            }

            $this->createViteDnsRecord(true);

            return;
        }

        if ($result->failed()) {
            throw new \RuntimeException($result->errorOutput());
        }

        info("<info>[✔]</info> Created DNS record: vite-{$this->hostname}");
    }

    protected function createHerdLink(): void
    {
        $result = spin(
            callback: fn () => Process::run("herd link {$this->hostname}"),
            message: 'Creating Laravel Herd link …'
        );

        if ($result->failed()) {
            throw new \RuntimeException($result->errorOutput());
        }

        info("<info>[✔]</info> Created Laravel Herd link.");
    }
}
