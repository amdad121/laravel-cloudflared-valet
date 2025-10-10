<?php

namespace Aerni\Cloudflared;

use Aerni\Cloudflared\Concerns\AssemblesPath;
use Illuminate\Support\Facades\File;

class TunnelConfig
{
    use AssemblesPath;

    public function __construct(public readonly ProjectConfig $projectConfig)
    {
        //
    }

    public static function make(ProjectConfig $projectConfig): static
    {
        return new static($projectConfig);
    }

    public function save(): void
    {
        File::put($this->path(), <<<YAML
tunnel: {$this->projectConfig->tunnel}
credentials-file: {$this->credentialsPath()}

ingress:
  - hostname: {$this->projectConfig->hostname}
    service: {$this->service()}
  - service: http_status:404
YAML);
    }

    public function delete(): void
    {
        File::delete($this->path());
    }

    public function service(): string
    {
        return config('app.url');
    }

    public function url(): string
    {
        return parse_url($this->service(), PHP_URL_SCHEME).'://'.$this->projectConfig->hostname;
    }

    public function credentialsPath(): string
    {
        return $this->assemble(getenv('HOME'), '.cloudflared', "{$this->projectConfig->tunnel}.json");
    }

    public function path(): string
    {
        return $this->assemble(getenv('HOME'), '.cloudflared', "{$this->projectConfig->tunnel}.yaml");
    }
}
