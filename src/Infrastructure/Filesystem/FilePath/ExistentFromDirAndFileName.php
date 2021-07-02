<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\FilePath;

use Exception;
use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\Filesystem\Filename;
use RC\Infrastructure\Filesystem\FilePath;

class ExistentFromDirAndFileName extends FilePath
{
    private $filepath;

    public function __construct(DirPath $dirPath, Filename $filename)
    {
        if (!$dirPath->exists()) {
            throw new Exception(sprintf('Directory %s does not exist', $dirPath->value()));
        }
        $filePath = sprintf('%s/%s', $dirPath->value(), $filename->value());
        $canonicalized = realpath($filePath);
        if ($canonicalized === false) {
            throw new Exception(sprintf('File %s does not exist', $filePath));
        }
        if (!is_file($canonicalized)) {
            throw new Exception(sprintf('%s is not a file', $filePath));
        }

        $this->filepath = $canonicalized;
    }

    public function value(): string
    {
        return $this->filepath;
    }

    public function exists(): bool
    {
        return true;
    }
}