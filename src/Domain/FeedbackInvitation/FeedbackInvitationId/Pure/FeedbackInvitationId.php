<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\FeedbackInvitationId\Pure;

interface FeedbackInvitationId
{
    public function value(): string;

    public function exists(): bool;
}