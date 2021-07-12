<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\FilePath;

use RC\Infrastructure\Filesystem\FileContents;
use RC\Infrastructure\Filesystem\FilePath;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;

class Removed extends FilePath
{
    private $filePath;
    private $cached;

    public function __construct(FilePath $filePath)
    {
        $this->filePath = $filePath;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    public function exists(): bool
    {
        return $this->filePath->exists();
    }

    private function doValue(): ImpureValue
    {
        if (!$this->filePath->value()->isSuccessful()) {
            return $this->filePath->value();
        }
        if (!$this->filePath->exists()) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not remove file %s because it does not exist', $this->filePath->value()->pure()->raw()),
                        []
                    )
                );
        }

        $r = unlink($this->filePath->value()->pure()->raw());

        if ($r === false) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not remove %s', $this->filePath->value()->pure()->raw()),
                        []
                    )
                );
        }

        return new Successful(new Emptie());
    }
}