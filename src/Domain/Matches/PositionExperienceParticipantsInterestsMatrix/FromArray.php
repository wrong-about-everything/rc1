<?php

declare(strict_types=1);

namespace RC\Domain\Matches\PositionExperienceParticipantsInterestsMatrix;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromArray implements PositionsExperiencesParticipantsInterestsMatrix
{
    private $matrix;

    public function __construct(array $matrix)
    {
        $this->matrix = $matrix;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->matrix));
    }
}