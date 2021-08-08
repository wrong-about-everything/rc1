<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\WriteModel;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface FeedbackInvitation
{
    public function value(): ImpureValue;
}