<?php

declare(strict_types=1);

namespace RC\Domain\BooleanAnswer\BooleanAnswerId\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class BooleanAnswer
{
    abstract public function value(): ImpureValue;

    abstract public function exists(): ImpureValue;

    final public function equals(BooleanAnswer $booleanAnswer): bool
    {
        return $this->value()->pure()->raw() === $booleanAnswer->value()->pure()->raw();
    }
}