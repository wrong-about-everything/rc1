<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging;

use RC\Infrastructure\Logging\LogId;

interface Logs
{
    public function add(LogItem $item): void;

    public function logId(): LogId;
}
