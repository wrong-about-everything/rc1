<?php

declare(strict_types=1);

namespace RC\Domain\Position\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class Position
{
    abstract public function value(): ImpureValue;

    abstract public function exists(): ImpureValue;

    final public function equals(Position $position): bool
    {
        return $this->value()->pure()->raw() === $position->value()->pure()->raw();
    }
}