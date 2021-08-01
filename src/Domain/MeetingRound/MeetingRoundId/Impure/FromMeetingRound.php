<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRound\MeetingRoundId\Impure;

use RC\Domain\MeetingRound\MeetingRoundId\Pure\FromString;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\NonExistent;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromMeetingRound extends MeetingRoundId
{
    private $meetingRound;
    private $concrete;

    public function __construct(MeetingRound $meetingRound)
    {
        $this->meetingRound = $meetingRound;
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
        if (!$this->meetingRound->value()->isSuccessful()) {
            return new NonSuccessful($this->meetingRound->value());
        }
        if (!$this->meetingRound->value()->pure()->isPresent()) {
            return new FromPure(new NonExistent());
        }

        return new FromPure(new FromString($this->meetingRound->value()->pure()->raw()['id']));
    }
}