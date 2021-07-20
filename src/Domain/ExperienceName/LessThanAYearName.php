<?php

declare(strict_types=1);

namespace RC\Domain\ExperienceName;

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