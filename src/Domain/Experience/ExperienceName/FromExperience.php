<?php

declare(strict_types=1);

namespace RC\Domain\Experience\ExperienceName;

use RC\Domain\Experience\ExperienceId\Pure\BetweenAYearAndThree;
use RC\Domain\Experience\ExperienceId\Pure\BetweenThreeYearsAndSix;
use RC\Domain\Experience\ExperienceId\Pure\Experience;
use RC\Domain\Experience\ExperienceId\Pure\GreaterThanSix;
use RC\Domain\Experience\ExperienceId\Pure\LessThanAYear;

class FromExperience extends ExperienceName
{
    private $experienceName;

    public function __construct(Experience $experience)
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

    private function concrete(Experience $experience): ExperienceName
    {
        return [
            (new LessThanAYear())->value() => new LessThanAYearName(),
            (new BetweenAYearAndThree())->value() => new BetweenAYearAndThreeName(),
            (new BetweenThreeYearsAndSix())->value() => new BetweenThreeYearsAndSixName(),
            (new GreaterThanSix())->value() => new GreaterThanSixYearsName(),
        ][$experience->value()] ?? new NonExistent();
    }
}