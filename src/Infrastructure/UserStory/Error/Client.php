<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory\Error;

use RC\Infrastructure\UserStory\Error;

class Client extends Error
{
    public function value(): int
    {
        return 1;
    }
}