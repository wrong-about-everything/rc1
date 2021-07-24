<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\InvitationId\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface InvitationId
{
    public function value(): ImpureValue;
}