<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\Reply;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface Reply
{
    public function value(): ImpureValue;
}