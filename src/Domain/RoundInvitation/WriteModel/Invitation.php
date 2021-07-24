<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\WriteModel;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface Invitation
{
    public function value(): ImpureValue;
}