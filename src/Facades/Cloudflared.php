<?php

namespace Aerni\Cloudflared\Facades;

use Illuminate\Support\Facades\Facade;

class Cloudflared extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Aerni\Cloudflared\Cloudflared::class;
    }
}
