<?php

declare(strict_types=1);

namespace RC\Domain\BooleanAnswer\BooleanAnswerId\Pure;

use RC\Infrastructure\TelegramBot\UserMessage\Pure\FromParsedTelegramMessage;

class FromTelegramMessage extends BooleanAnswer
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
        return $this->booleanAnswer()->value();
    }

    public function exists(): bool
    {
        return $this->booleanAnswer()->exists();
    }

    private function booleanAnswer()
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doBooleanAnswer();
        }

        return $this->cached;
    }

    private function doBooleanAnswer()
    {
        $userMessage = new FromParsedTelegramMessage($this->message);
        if (!$userMessage->value()->isSuccessful()) {
            return $userMessage->value();
        }

        return new FromInteger($userMessage->value()->pure()->raw());
    }
}