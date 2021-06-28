<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem;

abstract class FilePath
{
    /**
     * @return string Absolute path value
     */
    abstract public function value(): string;

    abstract public function exists(): bool;

    final public function equals(FilePath $filePath): bool
    {
        return $this->value() === $filePath->value();
    }
}