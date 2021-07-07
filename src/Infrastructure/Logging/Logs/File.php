<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging\Logs;

use RC\Infrastructure\Filesystem\FilePath;
use RC\Infrastructure\Filesystem\ModifiedFileContents\AppendedConcurrentSafelyToExistingFile;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\Logging\LogItem;
use Exception;

class File implements Logs
{
    private $filePath;
    private $logId;

    public function __construct(FilePath $filePath, LogId $logId)
    {
        if (!$filePath->exists()) {
            throw new Exception(sprintf('You must use existent files to write logs to. But file %s does not exist', $filePath));
        }

        $this->filePath = $filePath;
        $this->logId = $logId;
    }

    public function add(LogItem $item): void
    {
        (new AppendedConcurrentSafelyToExistingFile(
            $this->filePath,
            json_encode(
                array_merge(
                    $item->value(),
                    ['log_id' => $this->logId->value()]
                )
            ) . PHP_EOL
        ))
            ->value();
    }
}
