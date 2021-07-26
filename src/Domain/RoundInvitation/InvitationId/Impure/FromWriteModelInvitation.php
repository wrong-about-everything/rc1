<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\InvitationId\Impure;

use RC\Domain\RoundInvitation\WriteModel\Invitation;
use RC\Infrastructure\ImpureInteractions\ImpureValue;

class FromWriteModelInvitation implements InvitationId
{
    private $invitation;

    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    public function value(): ImpureValue
    {
        return $this->invitation->value();
    }
}