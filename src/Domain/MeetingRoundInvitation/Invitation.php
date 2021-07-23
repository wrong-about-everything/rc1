<?php

declare(strict_types=1);

namespace RC\Domain\MeetingRoundInvitation;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface Invitation
{
    public function value(): ImpureValue;
}