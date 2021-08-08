<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\FeedbackInvitationId\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface FeedbackInvitationId
{
    public function value(): ImpureValue;
}