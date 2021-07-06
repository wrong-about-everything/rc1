<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory\Body;

use RC\Infrastructure\UserStory\Body;

class Emptie extends Body
{
    public function value(): string
    {
        return '';
    }
}