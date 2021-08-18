<?php

declare(strict_types=1);

namespace RC\Domain\About\Pure;

use Exception;

class Emptie implements About
{
    public function value(): string
    {
        throw new Exception('This about me is empty');
    }

    public function empty(): bool
    {
        return true;
    }

    public function exists(): bool
    {
        return true;
    }
}
