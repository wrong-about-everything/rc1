<?php

declare(strict_types=1);

namespace RC\Domain\Experience\AvailableExperiences;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface AvailableExperiences
{
    public function value(): ImpureValue;
}