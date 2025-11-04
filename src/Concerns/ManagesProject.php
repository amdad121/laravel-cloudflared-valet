<?php

namespace Aerni\Cloudflared\Concerns;

use Aerni\Cloudflared\ProjectConfig;
use Aerni\Cloudflared\TunnelConfig;

use function Laravel\Prompts\info;

trait ManagesProject
{
    protected function saveProjectConfig(ProjectConfig $projectConfig): void
    {
        $projectConfig->save();

        info(' ✔ Saved project config.');
    }

    protected function deleteProject(TunnelConfig $tunnelConfig): void
    {
        $tunnelConfig->delete();
        $tunnelConfig->projectConfig->delete();

        info(' ✔ Deleted project config.');
    }
}
