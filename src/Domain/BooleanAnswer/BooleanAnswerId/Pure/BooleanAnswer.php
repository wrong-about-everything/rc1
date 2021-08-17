<?php

declare(strict_types=1);

namespace RC\Domain\BooleanAnswer\BooleanAnswerId\Pure;

abstract class BooleanAnswer
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equals(BooleanAnswer $booleanAnswer): bool
    {
        return $this->value() === $booleanAnswer->value();
    }
}