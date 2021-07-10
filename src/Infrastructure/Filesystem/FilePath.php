<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class FilePath
{
    /**
     * @return ImpureValue Absolute path value
     */
    abstract public function value(): ImpureValue;

    abstract public function exists(): bool;

    final public function equals(FilePath $filePath): bool
    {
        return $this->value() === $filePath->value();
    }
}