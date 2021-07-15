<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Stub;

use RC\Infrastructure\TelegramBot\ChatId\ChatId;
use RC\Infrastructure\TelegramBot\UserId\UserId;

class StartMessage
{
    private $userId;
    private $chatId;

    public function __construct(UserId $userId, ChatId $chatId)
    {
        $this->userId = $userId;
        $this->chatId = $chatId;
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
        "text": "/start",
        "entities": [
            {
                "offset": 0,
                "length": 6,
                "type": "bot_command"
            }
        ]
    }
}
q
                    ,
                    $this->userId->value(),
                    $this->chatId->value()
                ),
                true
            );
    }
}