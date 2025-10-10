<?php

namespace Aerni\Cloudflared;

use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\File;

class ProjectConfig
{
    public function __construct(
        public readonly string $tunnel,
        public readonly string $hostname
    ) {
    }

    public static function make(string $tunnel, string $hostname): static
    {
        return new static($tunnel, $hostname);
    }

    public static function load(): static
    {
        return new static(...Yaml::parseFile(static::path()));
    }

    public function save(): void
    {
        File::put(static::path(), <<<YAML
tunnel: {$this->tunnel}
hostname: {$this->hostname}
YAML);
    }

    public function delete(): void
    {
        File::delete(static::path());
    }

    public static function path(): string
    {
        return base_path('.cloudflared.yaml');
    }
}
