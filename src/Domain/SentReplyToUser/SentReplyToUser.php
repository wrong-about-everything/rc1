<?php

declare(strict_types=1);

namespace RC\Domain\SentReplyToUser;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface SentReplyToUser
{
    public function value(): ImpureValue;
}