<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging\Logs;

use RC\Infrastructure\Logging\LogItem;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs;

class DevNull implements Logs
{
    private $logId;

    public function __construct(LogId $logId)
    {
        $this->logId = $logId;
    }

    public function add(LogItem $item): void
    {
    }

    public function logId(): LogId
    {
        return $this->logId;
    }
}
