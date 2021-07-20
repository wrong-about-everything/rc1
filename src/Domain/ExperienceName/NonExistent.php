<?php

declare(strict_types=1);

namespace RC\Domain\ExperienceName;

use Exception;

class NonExistent extends ExperienceName
{
    public function value(): string
    {
        throw new Exception('Experience name does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}