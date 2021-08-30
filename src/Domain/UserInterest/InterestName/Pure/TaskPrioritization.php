<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class TaskPrioritization extends InterestName
{
    public function value(): string
    {
        return 'Приоритизация задач';
    }

    public function exists(): bool
    {
        return true;
    }
}