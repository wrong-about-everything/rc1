<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\Reply\Buttons\Boolean\BooleanAnswerName;

use RC\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage;

class FromUserMessage extends BooleanAnswerName
{
    private $userMessage;

    public function __construct(UserMessage $userMessage)
    {
        $this->userMessage = $userMessage->exists() ? new FromString($userMessage->value()) : new NonExistent();
    }

    public function value(): string
    {
        return $this->userMessage->value();
    }

    public function exists(): bool
    {
        return $this->userMessage->exists();
    }
}