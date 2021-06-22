<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory\Code;

use RC\Infrastructure\UserStory\Code;

class ServerError extends Code
{
    public function value(): int
    {
        return 2;
    }
}