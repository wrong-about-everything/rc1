<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\FilePath;

use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\Filesystem\Filename;
use RC\Infrastructure\Filesystem\FilePath;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class ExistentFromDirAndFileName extends FilePath
{
    private $dirPath;
    private $filename;
    private $cached;

    public function __construct(DirPath $dirPath, Filename $filename)
    {
        $this->dirPath = $dirPath;
        $this->filename = $filename;
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
        return $this->value()->isSuccessful();
    }

    private function doValue(): ImpureValue
    {
        if (!$this->dirPath->exists()) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Directory %s does not exist', $this->dirPath->value()->pure()->raw()),
                        []
                    )
                );
        }

        $filePath = sprintf('%s/%s', $this->dirPath->value()->pure()->raw(), $this->filename->value());
        $canonicalized = realpath($filePath);
        if ($canonicalized === false) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('File %s does not exist', $filePath),
                        []
                    )
                );
        }
        if (!is_file($canonicalized)) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('%s is not a file', $filePath),
                        []
                    )
                );
        }

        return new Successful(new Present($canonicalized));
    }
}