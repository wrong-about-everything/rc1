<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Pure;

class FromString implements RoundRegistrationQuestionId
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