<?php

declare(strict_types=1);

namespace RC\Domain\Experience\ExperienceId\Impure;

use RC\Domain\Experience\ExperienceId\Pure\Experience as PureExperience;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure extends Experience
{
    private $experience;

    public function __construct(PureExperience $experience)
    {
        $this->experience = $experience;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->experience->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->experience->exists()));
    }
}