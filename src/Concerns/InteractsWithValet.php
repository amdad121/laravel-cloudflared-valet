<?php

namespace Aerni\Cloudflared\Concerns;

use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\info;

trait InteractsWithValet
{
    protected function verifyValetFoundInPath(): void
    {
        if (Process::run('valet --version')->failed()) {
            $this->fail('Laravel Valet not found in PATH.');
        }
    }

    protected function createValetLink(string $hostname): void
    {
        Process::run("valet link {$hostname}")->throw();

        info(" ✔ Created Valet link: {$hostname}");
    }

    protected function deleteValetLink(string $hostname): void
    {
        Process::run("valet unlink {$hostname}")->throw();

        info(" ✔ Deleted Valet link: {$hostname}");
    }

    protected function valetSiteName(): string
    {
        return basename(base_path());
    }
}
