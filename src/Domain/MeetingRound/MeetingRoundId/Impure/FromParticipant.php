<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRound\MeetingRoundId\Impure;

use RC\Domain\MeetingRound\MeetingRoundId\Pure\FromString;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\NonExistent;
use RC\Domain\Participant\ReadModel\Participant;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromParticipant extends MeetingRoundId
{
    private $participant;
    private $concrete;

    public function __construct(Participant $participant)
    {
        $this->participant = $participant;
        $this->concrete = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete(): MeetingRoundId
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete(): MeetingRoundId
    {
        if (!$this->participant->value()->isSuccessful()) {
            return new NonSuccessful($this->participant->value());
        }
        if (!$this->participant->value()->pure()->isPresent()) {
            return new FromPure(new NonExistent());
        }

        return new FromPure(new FromString($this->participant->value()->pure()->raw()['meeting_round_id']));
    }
}