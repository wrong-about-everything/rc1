<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserMessage\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface UserMessage
{
    public function value(): ImpureValue;

    public function exists(): ImpureValue;
}