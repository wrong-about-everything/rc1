<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRound\MeetingRoundId\Impure;

use RC\Domain\MeetingRound\MeetingRoundId\Pure\FromString;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\NonExistent;
use RC\Domain\RoundInvitation\ReadModel\Invitation;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromInvitation extends MeetingRoundId
{
    private $invitation;
    private $concrete;

    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
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
        if (!$this->invitation->value()->isSuccessful()) {
            return new NonSuccessful($this->invitation->value());
        }
        if (!$this->invitation->value()->pure()->isPresent()) {
            return new FromPure(new NonExistent());
        }

        return new FromPure(new FromString($this->invitation->value()->pure()->raw()['meeting_round_id']));
    }
}