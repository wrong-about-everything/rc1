<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\Status\Impure;

use RC\Domain\RoundInvitation\Invitation;
use RC\Domain\RoundInvitation\Status\Pure\FromInteger;
use RC\Domain\RoundInvitation\Status\Pure\NonExistent;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromInvitation extends Status
{
    private $invitation;

    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    public function exists(): ImpureValue
    {
        return $this->concrete()->exists();
    }

    public function value(): ImpureValue
    {
        return $this->concrete()->value();
    }

    private function concrete()
    {
        if (!$this->invitation->value()->isSuccessful()) {
            return new NonSuccessful($this->invitation->value());
        }
        if (!$this->invitation->value()->pure()->isPresent()) {
            return new FromPure(new NonExistent());
        }

        return new FromPure(new FromInteger($this->invitation->value()->pure()->raw()['status']));
    }
}