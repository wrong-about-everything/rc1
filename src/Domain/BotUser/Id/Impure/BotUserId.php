<?php

declare(strict_types=1);

namespace RC\Domain\BotUser\Id\Impure;

use RC\Infrastructure\ImpureInteractions\ImpureValue;

interface BotUserId
{
    public function value(): ImpureValue;
}