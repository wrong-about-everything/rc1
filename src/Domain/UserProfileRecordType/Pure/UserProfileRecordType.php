<?php

declare(strict_types=1);

namespace RC\Domain\UserProfileRecordType\Pure;

abstract class UserProfileRecordType
{
    abstract public function value(): int;

    final public function equals(UserProfileRecordType $userProfileRecordType): bool
    {
        return $this->value() === $userProfileRecordType->value();
    }
}