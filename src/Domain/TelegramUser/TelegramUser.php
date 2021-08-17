<?php

declare(strict_types=1);

namespace RC\Domain\TelegramUser;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface TelegramUser
{
    public function value(): ImpureValue;
}