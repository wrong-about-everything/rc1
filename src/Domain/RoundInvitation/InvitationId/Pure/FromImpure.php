<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\InvitationId\Pure;

use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId as ImpureInvitationId;

class FromImpure implements InvitationId
{
    private $impureInvitationId;

    public function __construct(ImpureInvitationId $impureInvitationId)
    {
        $this->impureInvitationId = $impureInvitationId;
    }

    public function value(): string
    {
        return $this->impureInvitationId->value()->pure()->raw();
    }
}