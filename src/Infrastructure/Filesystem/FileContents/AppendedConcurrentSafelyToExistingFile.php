<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\FileContents;

use RC\Infrastructure\Filesystem\FileContents;
use RC\Infrastructure\Filesystem\FilePath;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;

class AppendedConcurrentSafelyToExistingFile implements FileContents
{
    private $filePath;
    private $data;
    private $cached;

    public function __construct(FilePath $filePath, string $data)
    {
        $this->filePath = $filePath;
        $this->data = $data;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        if (!$this->filePath->exists()) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('File does not exist', []));
        }
        if (!$this->filePath->value()->isSuccessful()) {
            return $this->filePath->value();
        }

        $r =
            file_put_contents(
                $this->filePath->value()->pure()->raw(),
                $this->data,
                FILE_APPEND | LOCK_EX
            );
        if ($r === false) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Write "%s" to %s was not successful', $this->data, $this->filePath->value()->pure()->raw()),
                        []
                    )
                );
        }

        return new Successful(new Emptie());
    }
}