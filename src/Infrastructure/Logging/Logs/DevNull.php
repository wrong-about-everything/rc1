<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging\Logs;

use RC\Infrastructure\Logging\LogItem;
use RC\Infrastructure\Logging\Logs;

class DevNull implements Logs
{
    public function receive(LogItem $item): void
    {
    }

    public function flush(): void
    {
        // these logs are not flushable. See GoogleCloudLogs as a counterexample.
    }
}
