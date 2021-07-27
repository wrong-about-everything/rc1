<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure;

class Position extends RegistrationQuestionType
{
    public function value(): int
    {
        return 0;
    }
}