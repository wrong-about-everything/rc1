<?php

declare(strict_types=1);

namespace RC\Domain\Participant\ParticipantId\Impure;

use RC\Domain\Participant\ParticipantId\Pure\ParticipantId as PureParticipantId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromPure implements ParticipantId
{
    private $pureParticipantId;

    public function __construct(PureParticipantId $pureParticipantId)
    {
        $this->pureParticipantId = $pureParticipantId;
    }

    public function value(): ImpureValue
    {
        return new Successful(new Present($this->pureParticipantId->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->pureParticipantId->exists()));
    }
}