<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging\Logs;

use Google\Cloud\Logging\Entry;
use Google\Cloud\Logging\LoggingClient;
use RC\Infrastructure\Filesystem\FilePath;
use RC\Infrastructure\Logging\LogItem;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs;

class GoogleCloudLogs implements Logs
{
    private $projectId;
    private $logger;
    private $logId;
    private $receivedLogsEntries;

    public function __construct(string $projectId, string $logName, FilePath $keyFilePath, LogId $logId)
    {
        $this->projectId = $projectId;
        $this->logId = $logId;
        $this->receivedLogsEntries = [];
        $this->logger =
            (new LoggingClient([
                'projectId' => $projectId,
                'keyFilePath' => $keyFilePath->value()
            ]))
                ->logger($logName);
    }

    public function receive(LogItem $item): void
    {
        $this->receivedLogsEntries[] =
            $this->logger
                ->entry(
                    (isset($item->value()['data']) && !empty($item->value()['data']))
                        ? ['message' => $item->value()['message'], 'data' => $item->value()['data']]
                        : $item->value()['message'],
                    [
                        'severity' => $item->value()['severity'],
                        'trace' => sprintf('projects/%s/traces/%s', $this->projectId, $this->logId->value()),
                    ]
                );
    }

    public function flush(): void
    {
        var_dump('Flush his called');
        var_dump(
            array_map(
                function (Entry $e) {
                    return $e->info();
                },
                $this->receivedLogsEntries
            )
        );
        $this->logger->writeBatch($this->receivedLogsEntries);
    }
}
