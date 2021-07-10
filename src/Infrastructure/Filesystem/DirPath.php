<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class DirPath
{
    /**
     * @return ImpureValue Absolute canonicalized path value without trailing slash.
     */
    abstract public function value(): ImpureValue;

    abstract public function exists(): bool;

    final public function equals(DirPath $dirPath): bool
    {
        return $this->value() === $dirPath->value();
    }
}