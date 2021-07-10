<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging\Logs;

use RC\Infrastructure\Logging\LogItem;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs;

class StdOut implements Logs
{
    private $logId;

    public function __construct(LogId $logId)
    {
        $this->logId = $logId;
    }

    public function receive(LogItem $item): void
    {
        var_dump(
            array_merge(
                $item->value(),
                ['log_id' => $this->logId->value()]
            )
        );
    }

    public function flush(): void
    {
        // these logs are not flushable. See GoogleCloudLogs as a counterexample.
    }
}
