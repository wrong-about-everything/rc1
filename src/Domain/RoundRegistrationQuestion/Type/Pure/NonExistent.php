<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion\Type\Pure;

use Exception;

class NonExistent extends RoundRegistrationQuestionType
{
    public function value(): int
    {
        throw new Exception('This registration question type does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}