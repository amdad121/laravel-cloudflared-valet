<?php

namespace Aerni\Cloudflared\Facades;

use Illuminate\Support\Facades\Facade;

class ProjectConfig extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Aerni\Cloudflared\ProjectConfig::class;
    }
}
