<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\Reply\Text;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface ReplyToUser
{
    public function value(): ImpureValue;
}