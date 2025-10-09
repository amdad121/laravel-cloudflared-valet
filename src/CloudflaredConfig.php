<?php

namespace Aerni\Cloudflared;

use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\File;

class CloudflaredConfig
{
    public function __construct(
        public string $tunnel,
        public string $hostname
    ) {}

    public static function load(): self
    {
        return new self(...Yaml::parseFile(self::path()));
    }

    public static function path(): string
    {
        return base_path('.cloudflared.yaml');
    }

    public function save(): void
    {
        File::put(self::path(), <<<YAML
tunnel: {$this->tunnel}
hostname: {$this->hostname}
YAML);
    }

    public static function delete(): void
    {
        File::delete(self::path());
    }
}
