<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\TelegramBot\UserCommand;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\TelegramBot\AvailableTelegramBotCommands\FromArray;
use RC\Infrastructure\TelegramBot\UserCommand\FromTelegramMessage;
use RC\Infrastructure\TelegramBot\UserCommand\Start;
use RC\Infrastructure\TelegramBot\UserCommand\UserCommand;

class FromTelegramMessageTest extends TestCase
{
    public function testStartCommand()
    {
        $this->assertTrue(
            (new FromTelegramMessage(
                $this->message(new Start()),
                new FromArray([(new Start())->value() => new Start()])
            ))
                ->equals(new Start())
        );
    }

    public function testUnknownCommand()
    {
        $command =
            new FromTelegramMessage(
                $this->message(new Start()),
                new FromArray([])
            );
        $this->assertFalse($command->exists());
    }

    public function testInvalidCommandBody()
    {
        $command =
            new FromTelegramMessage(
                'hey lalaley',
                new FromArray([])
            );
        $this->assertFalse($command->exists());
    }

    private function message(UserCommand $command)
    {
        return
            sprintf(
                <<<m
{
    "update_id": 814237007,
    "message": {
        "message_id": 735708,
        "from": {
            "id": 245192624,
            "is_bot": false,
            "first_name": "Vadim",
            "last_name": "Samokhin",
            "username": "dremuchee_bydlo",
            "language_code": "ru"
        },
        "chat": {
            "id": 245192624,
            "first_name": "Vadim",
            "last_name": "Samokhin",
            "username": "dremuchee_bydlo",
            "type": "private"
        },
        "date": 1626255716,
        "text": "%s",
        "entities": [
            {
                "offset": 0,
                "length": 6,
                "type": "bot_command"
            }
        ]
    }
}
m
                ,
                $command->value()
            );
    }
}