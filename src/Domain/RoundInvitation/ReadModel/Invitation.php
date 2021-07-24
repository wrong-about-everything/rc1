<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\ReadModel;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface Invitation
{
    public function value(): ImpureValue;
}