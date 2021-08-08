<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\Status\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

abstract class Status
{
    abstract public function value(): ImpureValue;

    abstract function exists(): ImpureValue;

    final public function equals(Status $status)
    {
        return $this->value()->pure()->raw() === $status->value()->pure()->raw();
    }
}