<?php

namespace Aerni\Cloudflared;

class TunnelDetails
{
    public function __construct(
        public readonly string $id,
        public readonly string $name
    ) {}
}
