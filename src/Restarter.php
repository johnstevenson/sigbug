<?php

declare(strict_types=1);

namespace App;

use Composer\XdebugHandler\XdebugHandler;

class Restarter extends XdebugHandler
{
    protected function requiresRestart(bool $default): bool
    {
        return !in_array('--none', $_SERVER['argv'], true);
    }
}
