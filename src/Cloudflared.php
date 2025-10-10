<?php

namespace Aerni\Cloudflared;

use Illuminate\Support\Facades\File;

class Cloudflared
{
    public function isInstalled(): bool
    {
        return File::exists(ProjectConfig::path());
    }
}
