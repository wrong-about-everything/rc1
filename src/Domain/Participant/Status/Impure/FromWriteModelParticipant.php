<?php

declare(strict_types=1);

namespace RC\Domain\Participant\Status\Impure;

use RC\Domain\Participant\ReadModel\Participant;
use RC\Domain\Participant\Status\Pure\FromInteger;
use RC\Domain\Participant\Status\Pure\NonExistent;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromWriteModelParticipant extends Status
{
    private $participant;

    public function __construct(Participant $participant)
    {
        $this->participant = $participant;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    private function concrete(): Status
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doConcrete();
        }

        return $this->cached;
    }

    private function doConcrete(): Status
    {
        if (!$this->participant->value()->isSuccessful()) {
            return new NonSuccessful($this->participant->value());
        }
        if ($this->participant->exists()->pure()->raw() === false) {
            return new FromPure(new NonExistent());
        }

        return new FromPure(new FromInteger($this->participant->value()->pure()->raw()['status']));
    }
}