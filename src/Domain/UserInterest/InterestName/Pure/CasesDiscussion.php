<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class CasesDiscussion extends InterestName
{
    public function value(): string
    {
        return 'Обсуждение кейсов';
    }

    public function exists(): bool
    {
        return true;
    }
}