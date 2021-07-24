<?php

declare(strict_types=1);

namespace RC\Domain\RoundInvitation\InvitationId\Pure;

interface InvitationId
{
    public function value(): string;
}