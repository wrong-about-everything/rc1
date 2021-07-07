<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\FileContents;

use Exception;
use RC\Infrastructure\Filesystem\FileContents;
use RC\Infrastructure\Filesystem\FilePath;

class FromStdErr implements FileContents
{
    public function value(): string
    {
        return fopen('php://stderr');
    }
}