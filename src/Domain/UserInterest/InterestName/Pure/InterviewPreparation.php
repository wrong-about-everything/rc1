<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class InterviewPreparation extends InterestName
{
    public function value(): string
    {
        return 'Подготовка к собеседованию';
    }

    public function exists(): bool
    {
        return true;
    }
}