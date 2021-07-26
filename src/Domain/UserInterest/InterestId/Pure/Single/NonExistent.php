<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestId\Pure\Single;

use Exception;

class NonExistent extends InterestId
{
    public function value(): int
    {
        throw new Exception('This interest does not exist');
    }

    public function exists(): bool
    {
        return false;
    }
}