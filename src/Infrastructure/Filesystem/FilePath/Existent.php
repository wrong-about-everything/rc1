<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\FilePath;

use Exception;
use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\Filesystem\FilePath;

class Existent extends FilePath
{
    private $path;

    public function __construct(string $path)
    {
        if (!is_file($path)) {
            throw new Exception(sprintf('%s is not a file', $path));
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