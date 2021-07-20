<?php

declare(strict_types=1);

namespace RC\Domain\ExperienceName;

class FromString extends ExperienceName
{
    private $experienceName;

    public function __construct(string $experience)
    {
        $this->experienceName = $this->concrete($experience);
    }

    public function value(): string
    {
        return $this->experienceName->value();
    }

    public function exists(): bool
    {
        return $this->experienceName->exists();
    }

    private function concrete(string $experience): ExperienceName
    {
        return [
            (new LessThanAYearName())->value() => new LessThanAYearName(),
            (new BetweenAYearAndThreeName())->value() => new BetweenAYearAndThreeName(),
            (new BetweenThreeYearsAndSixName())->value() => new BetweenThreeYearsAndSixName(),
            (new GreaterThanSixYearsName())->value() => new GreaterThanSixYearsName(),
        ][$experience] ?? new NonExistent();
    }
}