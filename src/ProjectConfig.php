<?php

namespace Aerni\Cloudflared;

use Illuminate\Support\Str;

class ProjectConfig
{
    public function __construct(protected CloudflaredConfig $config)
    {
        //
    }

    public function path(): string
    {
        return $this->config->path();
    }

    public function tunnel(): string
    {
        return $this->config->tunnel;
    }

    public function hostname(): string
    {
        return $this->config->hostname;
    }

    public function service(): string
    {
        return config('app.url');
    }

    public function url(): string
    {
        return parse_url(config('app.url'), PHP_URL_SCHEME).'://'.$this->hostname();
    }

    public function tunnelCredentialsPath(): string
    {
        return $this->assemble(getenv('HOME'), '.cloudflared', "{$this->tunnel()}.json");
    }

    public function tunnelConfigPath(): string
    {
        return $this->assemble(getenv('HOME'), '.cloudflared', "{$this->tunnel()}.yaml");
    }

    protected function assemble(string ...$parts): string
    {
        return Str::of(implode('/', $parts))
            ->replace('\\', '/')
            ->replace('//', '/')
            ->toString();
    }
}
