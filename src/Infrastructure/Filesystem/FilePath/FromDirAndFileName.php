<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\FilePath;

use Exception;
use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\Filesystem\Filename;
use RC\Infrastructure\Filesystem\FilePath;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromDirAndFileName extends FilePath
{
    private $dirPath;
    private $filename;

    public function __construct(DirPath $dirPath, Filename $filename)
    {
        if (!$dirPath->exists()) {
            throw new Exception(sprintf('Directory %s does not exist', $dirPath));
        }

        $this->dirPath = $dirPath;
        $this->filename = $filename;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present(sprintf('%s/%s', $this->dirPath->value(), $this->filename->value())));
    }

    public function exists(): bool
    {
        return is_file($this->value()->pure()->raw());
    }
}