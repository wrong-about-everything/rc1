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

class Created extends FilePath
{
    private $filePath;
    private $contents;
    private $cached;

    public function __construct(FilePath $filePath, FileContents $contents)
    {
        $this->filePath = $filePath;
        $this->contents = $contents;
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
        return true;
    }

    private function doValue(): ImpureValue
    {
        if (!$this->filePath->value()->isSuccessful()) {
            return $this->filePath->value();
        }
        if ($this->filePath->exists()) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not create file %s because it already exists', $this->filePath->value()),
                        []
                    )
                );
        }

        $r = file_put_contents($this->filePath->value()->pure()->raw(), $this->contents->value()->pure()->raw(), LOCK_EX);

        if ($r === false) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not write to %s', $this->filePath->value()->pure()->raw()),
                        []
                    )
                );
        }

        return new Successful(new Emptie());
    }
}