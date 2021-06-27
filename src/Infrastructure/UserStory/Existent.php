<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory;

abstract class Existent implements UserStory
{
    public function exists(): bool
    {
        return true;
    }
}