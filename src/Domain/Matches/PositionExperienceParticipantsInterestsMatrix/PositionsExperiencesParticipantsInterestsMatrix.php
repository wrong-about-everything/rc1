<?php

declare(strict_types=1);

namespace RC\Domain\Matches\PositionExperienceParticipantsInterestsMatrix;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface PositionsExperiencesParticipantsInterestsMatrix
{
    public function value(): ImpureValue;
}