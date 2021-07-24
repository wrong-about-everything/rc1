<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserMessage\Pure;

interface UserMessage
{
    public function value(): string;

    public function exists(): bool;
}