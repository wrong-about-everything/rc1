<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\FileContents;

use Exception;
use RC\Infrastructure\Filesystem\FileContents;
use RC\Infrastructure\Filesystem\FilePath;

class FromFile implements FileContents
{
    private $filePath;

    public function __construct(FilePath $filePath)
    {
        if (!$filePath->exists()) {
            throw new Exception('File does not exist');
        }

        $this->filePath = $filePath;
    }

    public function value(): string
    {
        return file_get_contents($this->filePath->value());
    }
}