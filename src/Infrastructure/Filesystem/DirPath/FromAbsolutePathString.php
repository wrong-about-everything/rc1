<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\DirPath;

use Exception;
use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromAbsolutePathString extends DirPath
{
    private $path;

    public function __construct(string $path)
    {
        if (preg_match('/^(\/[A-Za-z0-9._-]+)+$/', $path) === 0) {
            throw new Exception(sprintf('Dir path %s is invalid. You must specify a valid absolute path, for example /usr/lib/gcc.', $path));
        }

        $this->path = $path;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->path));
    }

    public function exists(): bool
    {
        return is_dir($this->path);
    }
}