<?php

declare(strict_types=1);

namespace RC\Domain\PositionName;

abstract class PositionName
{
    abstract public function value(): string;

    abstract public function exists(): bool;

    final public function equals(PositionName $positionName): bool
    {
        return $this->value() === $positionName->value();
    }
}