<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRoundInvitation\Status\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class Status
{
    abstract public function value(): ImpureValue;

    abstract public function exists(): ImpureValue;

    final public function equals(Status $status): bool
    {
        return $this->value()->pure()->raw() === $status->value()->pure()->raw();
    }
}