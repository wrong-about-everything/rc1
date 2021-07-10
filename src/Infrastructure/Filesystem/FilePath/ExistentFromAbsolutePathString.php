<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\FilePath;

use Exception;
use RC\Infrastructure\Filesystem\FilePath;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class ExistentFromAbsolutePathString extends FilePath
{
    private $path;

    public function __construct(string $path)
    {
        if ($path[0] !== '/') {
            throw new Exception(sprintf('%s must be an absolute path', $path));
        }
        $canonicalized = realpath($path);
        if ($canonicalized === false) {
            throw new Exception(sprintf('%s does not exist', $path));
        }
        if (!is_file($canonicalized)) {
            throw new Exception(sprintf('%s is not a file', $path));
        }

        $this->path = $canonicalized;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->path));
    }

    public function exists(): bool
    {
        return true;
    }
}