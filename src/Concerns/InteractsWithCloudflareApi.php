<?php

namespace Aerni\Cloudflared\Concerns;

use Aerni\Cloudflared\Facades\Cloudflared;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

trait InteractsWithCloudflareApi
{
    // TODO: Can we use the same extracted API token to delete DNS records?
    protected function authenticatedDomain(): string
    {
        try {
            $certificate = Cloudflared::certificate();

            return Cache::rememberForever("cloudflared.domain.{$certificate->hash()}", function () use ($certificate) {
                return Http::withHeaders([
                    'Authorization' => "Bearer {$certificate->apiToken}",
                    'Content-Type' => 'application/json',
                ])
                    ->get("https://api.cloudflare.com/client/v4/zones/{$certificate->zoneId}")
                    ->throw()
                    ->json('result.name');
            });
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }
}
