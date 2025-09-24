<?php

namespace Aerni\Cloudflared\Console\Commands;

use Aerni\Cloudflared\Concerns\HasProjectConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\info;

class CloudflaredRun extends Command
{
    use HasProjectConfig;

    protected $signature = 'cloudflared:run';

    protected $description = 'Run the Cloudflare Tunnel of this project.';

    public function handle(): void
    {
        info('Starting cloudflared tunnel.');

        $this->createCloudflaredConfig();
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
        // Set up signal handlers before starting the process
        pcntl_async_signals(true);

        $process = Process::forever()
            ->tty()
            ->start("cloudflared tunnel --config {$this->tunnelConfigPath()} run");

        // Handle multiple termination signals
        $signalHandler = function ($signal) use ($process) {
            $process->signal(SIGTERM);
            $process->wait();
            $this->info('Stopped cloudflared tunnel.');
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
}
