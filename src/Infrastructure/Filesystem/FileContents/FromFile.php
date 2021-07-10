<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\FileContents;

use Exception;
use RC\Infrastructure\Filesystem\FileContents;
use RC\Infrastructure\Filesystem\FilePath;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromFile implements FileContents
{
    private $filePath;
    private $cached;

    public function __construct(FilePath $filePath)
    {
        if (!$filePath->exists()) {
            throw new Exception('File does not exist');
        }

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

    private function doValue(): ImpureValue
    {
        if (!$this->filePath->value()->isSuccessful()) {
            return $this->filePath->value();
        }

        $contents = file_get_contents($this->filePath->value()->pure()->raw());

        return
            $contents === false
                ?
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not read contents of %s', $this->filePath->value()),
                        []
                    )
                )
                : new Successful(new Present($contents));
    }
}