<?php

declare(strict_types=1);

namespace RC\Domain\Experience\ExperienceName;

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