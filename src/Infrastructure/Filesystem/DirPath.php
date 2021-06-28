<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem;

abstract class DirPath
{
    /**
     * @return string Absolute path value
     */
    abstract public function value(): string;

    abstract public function exists(): bool;

    final public function equals(DirPath $dirPath): bool
    {
        return $this->value() === $dirPath->value();
    }
}