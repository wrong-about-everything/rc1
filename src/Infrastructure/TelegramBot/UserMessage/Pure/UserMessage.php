<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserMessage\Pure;

interface UserMessage
{
    /**
     * A message that user sent.
     */
    public function value(): string;

    public function exists(): bool;
}