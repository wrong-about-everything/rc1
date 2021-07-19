<?php

declare(strict_types=1);

namespace RC\Domain\Position\Pure;

use RC\Infrastructure\TelegramBot\UserMessage\FromParsedTelegramMessage;

class FromTelegramMessage extends Position
{
    private $message;
    private $cached;

    public function __construct(array $message)
    {
        $this->message = $message;
        $this->cached = null;
    }

    public function value(): int
    {
        return $this->position()->value();
    }

    public function exists(): bool
    {
        return $this->position()->exists();
    }

    private function position()
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doPosition();
        }

        return $this->cached;
    }

    private function doPosition()
    {
        $userMessage = new FromParsedTelegramMessage($this->message);
        if (!$userMessage->value()->isSuccessful()) {
            return $userMessage->value();
        }

        return new FromInteger($userMessage->value()->pure()->raw());
    }
}