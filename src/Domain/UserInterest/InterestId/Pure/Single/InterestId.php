<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

abstract class InterestId
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equals(InterestId $interest): bool
    {
        return $this->value() === $interest->value();
    }
}