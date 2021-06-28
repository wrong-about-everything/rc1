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

    public function add(LogItem $item): void
    {
        var_dump(
            array_merge(
                $item->value(),
                ['log_id' => $this->logId->value()]
            )
        );
    }

    public function logId(): LogId
    {
        return $this->logId;
    }
}
