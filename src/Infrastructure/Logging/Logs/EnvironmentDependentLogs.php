<?php

declare(strict_types=1);

namespace RC\Infrastructure\Logging\Logs;

use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\Filesystem\DirPath\ExistentFromNestedDirectoryNames;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Infrastructure\Filesystem\FilePath\ExistentFromDirAndFileName;
use RC\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use RC\Infrastructure\Logging\LogItem;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs;

class EnvironmentDependentLogs implements Logs
{
    private $concrete;
    private $logId;

    public function __construct(DirPath $root, LogId $logId)
    {
        $this->concrete = $this->concrete($root, $logId);
        $this->logId = $logId;
    }

    public function add(LogItem $item): void
    {
        $this->concrete->add($item);
    }

    private function concrete(DirPath $root, LogId $logId): Logs
    {
        if ((new FromDirAndFileName($root, new PortableFromString('.env.dev')))->exists()) {
            return
                new File(
                    new ExistentFromDirAndFileName(
                        new ExistentFromNestedDirectoryNames(
                            $root,
                            new PortableFromString('logs')
                        ),
                        new PortableFromString('log.json')
                    ),
                    $logId
                );
        }

        return new StdErr($logId);
    }
}
