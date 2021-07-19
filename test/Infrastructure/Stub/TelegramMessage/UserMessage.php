<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Stub\TelegramMessage;

use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;

class UserMessage
{
    private $telegramUserId;
    private $text;

    public function __construct(TelegramUserId $telegramUserId, string $text)
    {
        $this->telegramUserId = $telegramUserId;
        $this->text = $text;
    }

    public function value(): array
    {
        return
            json_decode(
                sprintf(
                    <<<q
{
    "update_id": 814185830,
    "message": {
        "message_id": 726138,
        "from": {
            "id": %d,
            "is_bot": false,
            "first_name": "Vadim",
            "last_name": "Samokhin",
            "username": "dremuchee_bydlo"
        },
        "chat": {
            "id": %d,
            "first_name": "Vadim",
            "last_name": "Samokhin",
            "username": "dremuchee_bydlo",
            "type": "private"
        },
        "date": 1625481534,
        "text": "%s"
    }
}
q
                    ,
                    $this->telegramUserId->value(),
                    $this->telegramUserId->value(),
                    $this->text
                ),
                true
            );
    }
}