<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory;

use Exception;

class NonExistent implements UserStory
{
    public function response(): Response
    {
        throw new Exception('This user story does not have a matching route');
    }

    public function exists(): bool
    {
        return false;
    }
}