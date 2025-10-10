<?php

namespace Aerni\Cloudflared;

use Illuminate\Support\Facades\File;

class ProjectConfig
{
    public function __construct(
        public readonly string $tunnel,
        public readonly string $hostname
    ) {}

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
