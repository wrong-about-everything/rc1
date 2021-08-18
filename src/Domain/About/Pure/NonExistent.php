<?php

declare(strict_types=1);

namespace RC\Domain\About\Pure;

use Exception;

class NonExistent implements About
{
    public function value(): string
    {
        throw new Exception('This about me does not exist');
    }

    public function empty(): bool
    {
        throw new Exception('This about me does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}
