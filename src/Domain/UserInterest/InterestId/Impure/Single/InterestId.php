<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Impure\Single;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class InterestId
{
    abstract public function value(): ImpureValue;

    final public function equals(InterestId $userInterest): bool
    {
        return $this->value()->pure()->raw() === $userInterest->value()->pure()->raw();
    }
}