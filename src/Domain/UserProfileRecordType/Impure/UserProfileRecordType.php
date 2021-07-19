<?php

declare(strict_types=1);

namespace RC\Domain\UserProfileRecordType\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class UserProfileRecordType
{
    abstract public function value(): ImpureValue;

    final public function equals(UserProfileRecordType $userProfileRecordType): bool
    {
        return $this->value()->pure()->raw() === $userProfileRecordType->value()->pure()->raw();
    }
}