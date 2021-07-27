<?php

declare(strict_types=1);

namespace RC\Domain\Participant\ParticipantId\Impure;

use RC\Domain\Participant\WriteModel\Participant;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromWriteModelParticipant implements ParticipantId
{
    private $participant;

    public function __construct(Participant $participant)
    {
        $this->participant = $participant;
    }

    public function value(): ImpureValue
    {
        if (!$this->participant->value()->isSuccessful()) {
            return $this->participant->value();
        }

        return $this->participant->value();
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->participant->value()->pure()->isPresent()));
    }
}