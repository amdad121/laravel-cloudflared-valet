<?php

namespace Aerni\Cloudflared\Console\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\info;
use Aerni\Cloudflared\ProjectConfig;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

use Aerni\Cloudflared\Concerns\HasProjectConfig;
use Aerni\Cloudflared\Concerns\InteractsWithHerd;

class CloudflaredStart extends Command
{
    use HasProjectConfig, InteractsWithHerd;

    protected $signature = 'cloudflared:start';

    protected $description = 'Run the Cloudflare Tunnel of this project.';

    public function __construct(protected ProjectConfig $config)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        if (! File::exists($this->config->path())) {
            $this->fail("Missing file <info>.cloudflared.yaml</info>. There is nothing to uninstall.");
        }

        $this->createCloudflaredConfig();
        $this->createHerdLink($this->hostname());
        $this->runCloudflared();
    }

    protected function createCloudflaredConfig(): void
    {
        file_put_contents($this->tunnelConfigPath(), $this->cloudflaredConfigContents());
    }

    // TODO: Only show process output if it was requested via a --debug or --logLevel or something like that.
    // Else, only show errors.

    protected function runCloudflared(): void
    {
        info('<info>[✔]</info> Started tunnel');

        // Set up signal handlers before starting the process
        pcntl_async_signals(true);

        $process = Process::forever()
            ->tty()
            ->start("cloudflared tunnel --config {$this->tunnelConfigPath()} run");

        // Handle multiple termination signals
        $signalHandler = function ($signal) use ($process) {
            $process->signal(SIGTERM);
            $process->wait();
            $this->cleanupCloudflaredProcess();
            exit(0);
        };

        pcntl_signal(SIGINT, $signalHandler);  // Ctrl+C
        pcntl_signal(SIGTERM, $signalHandler); // Termination signal
        pcntl_signal(SIGHUP, $signalHandler);  // Hangup signal

        try {
            $process->wait()->throw();
        } catch (\Exception $e) {
            // Ensure process is terminated on any failure
            if ($process->running()) {
                $process->signal(SIGTERM);
                $process->wait();
            }
            throw $e;
        }
    }

    protected function cleanupCloudflaredProcess(): void
    {
        info('<info>[✔]</info> Stopped tunnel');

        File::delete($this->tunnelConfigPath());

        $this->deleteHerdLink($this->hostname());
    }
}
