<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Impure\Multiple;

use RC\Domain\UserInterest\InterestId\Impure\Single\InterestId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class UserInterestIds
{
    abstract public function value(): ImpureValue;

    final public function contain(InterestId $userInterest): bool
    {
        return in_array($userInterest->value()->pure()->raw(), $this->value()->pure()->raw());
    }
}