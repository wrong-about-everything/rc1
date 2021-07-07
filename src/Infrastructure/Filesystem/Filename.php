<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem;

/**
 * In linux, directories are files. Hence, the same naming conventions apply to both.
 */
abstract class Filename
{
    abstract public function value(): string;

    abstract public function exists(): bool;

    final public function equals(Filename $filename): bool
    {
        return $this->value() === $filename->value();
    }
}