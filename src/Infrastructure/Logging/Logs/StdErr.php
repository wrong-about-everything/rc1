<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging\Logs;

use RC\Infrastructure\Logging\LogItem;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs;

class StdErr implements Logs
{
    private $logId;
    private $stdErr;

    public function __construct(LogId $logId)
    {
        $this->logId = $logId;
        $this->stdErr = fopen('php://stderr', 'wb');;
    }

    public function add(LogItem $item): void
    {
        fwrite(
            $this->stdErr,
            json_encode(
                array_merge(
                    $item->value(),
                    ['log_id' => $this->logId->value()]
                )
            )
        );
    }
}
