<?php

declare(strict_types=1);

namespace RC\Domain\Participant\ParticipantId\Impure;

use RC\Domain\Participant\ReadModel\Participant;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromReadModelParticipant implements ParticipantId
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

        return new Successful(new Present($this->participant->value()->pure()->raw()['id']));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->participant->value()->pure()->isPresent()));
    }
}