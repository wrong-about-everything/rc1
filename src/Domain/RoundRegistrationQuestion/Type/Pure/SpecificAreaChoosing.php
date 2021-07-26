<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion\Type\Pure;

class SpecificAreaChoosing extends RoundRegistrationQuestionType
{
    public function value(): int
    {
        return 1;
    }

    public function exists(): bool
    {
        return true;
    }
}