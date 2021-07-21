<?php

declare(strict_types=1);

namespace RC\Domain\Experience\ExperienceId\Pure;

use RC\Domain\Experience\ExperienceName\BetweenAYearAndThreeName;
use RC\Domain\Experience\ExperienceName\BetweenThreeYearsAndSixName;
use RC\Domain\Experience\ExperienceName\ExperienceName;
use RC\Domain\Experience\ExperienceName\GreaterThanSixYearsName;
use RC\Domain\Experience\ExperienceName\LessThanAYearName;

class FromExperienceName extends Experience
{
    private $concrete;

    public function __construct(ExperienceName $experienceName)
    {
        $this->concrete = isset($this->all()[$experienceName->value()]) ? $this->all()[$experienceName->value()] : new NonExistent();
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function all()
    {
        return [
            (new LessThanAYearName())->value() => new LessThanAYear(),
            (new BetweenAYearAndThreeName())->value() => new BetweenAYearAndThree(),
            (new BetweenThreeYearsAndSixName())->value() => new BetweenThreeYearsAndSix(),
            (new GreaterThanSixYearsName())->value() => new GreaterThanSix(),
        ];
    }
}