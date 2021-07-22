<?php

declare(strict_types=1);

namespace RC\UserStories\Cron\InvitesToTakePartInANewRound\Invitation;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface Invitation
{
    public function value(): ImpureValue;
}