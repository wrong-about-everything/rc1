<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

use Exception;

class NonExistent extends InterestName
{
    public function value(): string
    {
        throw new Exception('This interest name does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}