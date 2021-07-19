<?php

declare(strict_types=1);

namespace RC\Domain\Experience\Pure;

use RC\Infrastructure\TelegramBot\UserMessage\FromParsedTelegramMessage;

class FromTelegramMessage extends Experience
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
        return $this->experience()->value();
    }

    public function exists(): bool
    {
        return $this->experience()->exists();
    }

    private function experience()
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doExperience();
        }

        return $this->cached;
    }

    private function doExperience()
    {
        $userMessage = new FromParsedTelegramMessage($this->message);
        if (!$userMessage->value()->isSuccessful()) {
            return $userMessage->value();
        }

        return new FromInteger($userMessage->value()->pure()->raw());
    }
}