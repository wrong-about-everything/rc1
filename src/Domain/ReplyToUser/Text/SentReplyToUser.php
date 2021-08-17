<?php

declare(strict_types=1);

namespace RC\Domain\ReplyToUser\Text;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface SentReplyToUser
{
    public function value(): ImpureValue;
}