<?php

declare(strict_types=1);

namespace RC\Domain\Experience\ExperienceName;

class LessThanAYearName extends ExperienceName
{
    public function value(): string
    {
        return 'Меньше года';
    }

    public function exists(): bool
    {
        return true;
    }
}