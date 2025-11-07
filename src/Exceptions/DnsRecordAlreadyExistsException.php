<?php

namespace Aerni\Cloudflared\Exceptions;

use Exception;

class DnsRecordAlreadyExistsException extends Exception
{
    public function __construct(public readonly string $hostname)
    {
        parent::__construct("DNS record {$hostname} already exists");
    }
}
