<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory\Body;

use RC\Domain\TelegramBot\Method\SendMessage;
use RC\Infrastructure\ImpureInteractions\PureValue;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\UserStory\Body;

// @todo: Попробовать этот класс вместо http-запроса
class ReplyToTelegramUser extends Body
{
    private $chatId;
    private $reply;

    public function __construct(int $chatId, string $reply)
    {
        $this->chatId = $chatId;
        $this->reply = $reply;
    }

    public function value(): PureValue
    {
        return
            new Present([
                'method' => (new SendMessage())->value(),
                'chat_id' => $this->chatId,
                'text' => $this->reply,
            ]);
    }
}