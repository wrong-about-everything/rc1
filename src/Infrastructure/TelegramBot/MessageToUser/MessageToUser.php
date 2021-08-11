<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\MessageToUser;

interface MessageToUser
{
    public function value(): string;
}