<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\ModifiedFileContents;

use Exception;
use RC\Infrastructure\Filesystem\FilePath;
use RC\Infrastructure\Filesystem\ModifiedFileContents;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;

class AppendedConcurrentSafelyToExistingFile implements ModifiedFileContents
{
    private $filePath;
    private $data;

    public function __construct(FilePath $filePath, string $data)
    {
        if (!$filePath->exists()) {
            throw new Exception('File does not exist');
        }

        $this->filePath = $filePath;
        $this->data = $data;
    }

    public function value(): ImpureValue
    {
        $r =
            file_put_contents(
                $this->filePath->value(),
                $this->data,
                FILE_APPEND | LOCK_EX
            );
        if ($r === false) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Write "%s" to %s was not successful', $this->data, $this->filePath->value()),
                        []
                    )
                );
        }

        return new Successful(new Emptie());
    }
}