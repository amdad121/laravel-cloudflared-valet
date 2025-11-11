<?php

namespace Aerni\Cloudflared\Exceptions;

use Exception;

class NotATunnelDnsRecordException extends Exception
{
    public function __construct(public readonly string $hostname)
    {
        parent::__construct("DNS record {$hostname} is not a Cloudflare Tunnel record");
    }
}
