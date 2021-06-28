<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\DirPath;

use Exception;
use RC\Infrastructure\Filesystem\DirPath;

class Existent extends DirPath
{
    private $path;

    public function __construct(string $path)
    {
        if (!is_dir($path)) {
            throw new Exception(sprintf('%s is not a directory', $path));
        }

        $this->path = $path;
    }

    public function value(): string
    {
        return realpath($this->path);
    }

    public function exists(): bool
    {
        return true;
    }
}