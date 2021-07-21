<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionId\Pure;

abstract class Position
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equals(Position $position): bool
    {
        return $this->value() === $position->value();
    }
}