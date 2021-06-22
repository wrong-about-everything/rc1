<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory\Headers;

use RC\Infrastructure\UserStory\Headers;

class Emptie extends Headers
{
    public function value(): array
    {
        return [];
    }
}