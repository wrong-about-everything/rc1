<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure;

abstract class RegistrationQuestionType
{
    abstract public function value(): int;

    final public function equals(RegistrationQuestionType $registrationQuestionType): bool
    {
        return $this->value() === $registrationQuestionType->value();
    }
}