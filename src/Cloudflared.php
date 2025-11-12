<?php

namespace Aerni\Cloudflared;

class Cloudflared
{
    public function projectConfig(): ProjectConfig
    {
        return once(fn () => ProjectConfig::load());
    }

    public function tunnelConfig(): TunnelConfig
    {
        return once(fn () => new TunnelConfig($this->projectConfig()));
    }

    public function isInstalled(): bool
    {
        return ProjectConfig::exists();
    }
}
