<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\DirPath;

use Exception;
use RC\Infrastructure\Filesystem\DirPath;

class ExistentFromAbsolutePath extends DirPath
{
    private $path;

    public function __construct(string $path)
    {
        if ($path[0] !== '/') {
            throw new Exception(sprintf('You must specify an absolute path of %s directory', $path));
        }
        $canonicalized = realpath($path);
        if ($canonicalized === false) {
            throw new Exception(sprintf('%s does not exist', $path));
        }
        if (!is_dir($canonicalized)) {
            throw new Exception(sprintf('%s is not a directory', $path));
        }

        $this->path = $canonicalized;
    }

    public function value(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return true;
    }
}