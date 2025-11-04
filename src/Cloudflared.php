<?php

namespace Aerni\Cloudflared;

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class Cloudflared
{
    public function makeProjectConfig(string $id, string $name, string $hostname, bool $vite = false): ProjectConfig
    {
        return new ProjectConfig($id, $name, $hostname, $vite);
    }

    public function projectConfig(): ProjectConfig
    {
        return once(function () {
            if (! $this->isInstalled()) {
                throw new \RuntimeException('No project configuration found. Run "php artisan cloudflared:install" first.');
            }

            return $this->makeProjectConfig(...Yaml::parseFile(ProjectConfig::path()));
        });
    }

    public function tunnelConfig(): TunnelConfig
    {
        return once(fn () => new TunnelConfig($this->projectConfig()));
    }

    public function isInstalled(): bool
    {
        return File::exists(ProjectConfig::path());
    }
}
