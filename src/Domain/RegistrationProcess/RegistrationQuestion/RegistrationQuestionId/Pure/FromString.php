<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationProcess\RegistrationQuestion\RegistrationQuestionId\Pure;

class FromString implements RegistrationQuestionId
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}