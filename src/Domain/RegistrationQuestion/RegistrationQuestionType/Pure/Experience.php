<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure;

class Experience extends RegistrationQuestionType
{
    public function value(): int
    {
        return 1;
    }
}