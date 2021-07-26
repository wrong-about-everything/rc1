<?php

declare(strict_types=1);

namespace RC\Domain\UserInterest\InterestName\Pure;

class SpecificArea extends InterestName
{
    public function value(): string
    {
        return 'Обсудить конкретную тему: проблему, идею или задачу';
    }

    public function exists(): bool
    {
        return true;
    }
}