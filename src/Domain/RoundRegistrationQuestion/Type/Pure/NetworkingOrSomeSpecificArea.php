<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion\Type\Pure;

class NetworkingOrSomeSpecificArea extends RoundRegistrationQuestionType
{
    public function value(): int
    {
        return 0;
    }

    public function exists(): bool
    {
        return true;
    }
}