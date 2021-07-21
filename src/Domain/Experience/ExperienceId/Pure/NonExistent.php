<?php

declare(strict_types=1);

namespace RC\Domain\Experience\ExperienceId\Pure;

use Exception;

class NonExistent extends Experience
{
    public function value(): int
    {
        throw new Exception('This experience does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}