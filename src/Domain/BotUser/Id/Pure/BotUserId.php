<?php

declare(strict_types=1);

namespace RC\Domain\BotUser\Id\Pure;

interface BotUserId
{
    public function value(): string;
}