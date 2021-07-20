<?php

declare(strict_types=1);

namespace RC\Domain\ExperienceName;

class GreaterThanSixYearsName extends ExperienceName
{
    public function value(): string
    {
        return 'Больше шести лет';
    }

    public function exists(): bool
    {
        return true;
    }
}