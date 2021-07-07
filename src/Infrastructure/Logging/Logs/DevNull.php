<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging\Logs;

use RC\Infrastructure\Logging\LogItem;
use RC\Infrastructure\Logging\Logs;

class DevNull implements Logs
{
    public function add(LogItem $item): void
    {
    }
}
