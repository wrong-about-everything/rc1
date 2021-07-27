<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure;

class About extends RegistrationQuestionType
{
    public function value(): int
    {
        return 2;
    }
}