<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem;

abstract class Filename
{
    abstract public function value(): string;

    abstract public function exists(): bool;

    final public function equals(Filename $filename): bool
    {
        return $this->value() === $filename->value();
    }
}