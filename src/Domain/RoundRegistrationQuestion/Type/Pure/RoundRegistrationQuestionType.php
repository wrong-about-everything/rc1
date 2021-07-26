<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion\Type\Pure;

abstract class RoundRegistrationQuestionType
{
    abstract public function value(): int;

    abstract public function exists(): bool;

    final public function equals(RoundRegistrationQuestionType $questionType): bool
    {
        return $this->value() === $questionType->value();
    }
}