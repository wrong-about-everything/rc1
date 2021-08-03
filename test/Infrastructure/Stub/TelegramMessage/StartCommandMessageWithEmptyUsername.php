<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Stub\TelegramMessage;

use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;

class StartCommandMessageWithEmptyUsername
{
    private $userId;

    public function __construct(TelegramUserId $userId)
    {
        $this->userId = $userId;
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
            "last_name": "Samokhin"
        },
        "chat": {
            "id": %d,
            "first_name": "Vadim",
            "last_name": "Samokhin",
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
                    $this->userId->value()
                ),
                true
            );
    }
}