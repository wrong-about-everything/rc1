<?php

declare(strict_types=1);

namespace RC\Tests\Unit\UserStories\User\PressesStart;

use PHPUnit\Framework\TestCase;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs\InMemory;
use RC\Infrastructure\Logging\Logs\StdOut;
use RC\Infrastructure\TelegramBot\BotId\BotId;
use RC\Infrastructure\TelegramBot\BotId\FromString;
use RC\Infrastructure\TelegramBot\ChatId\ChatId;
use RC\Infrastructure\TelegramBot\ChatId\FromInteger as ChatIdFromInteger;
use RC\Infrastructure\TelegramBot\UserId\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\UserId;
use RC\Infrastructure\Uuid\Fixed;
use RC\Infrastructure\Uuid\RandomUUID;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\StartMessage;
use RC\UserStories\User\PressesStart\PressesStart;

class PressesStartTest extends TestCase
{
    public function testWhenNewUserPressesStartThenGreetingMessageIsSent()
    {
        $connection = new ApplicationConnection();
        $logs = new InMemory(new LogId(new RandomUUID()));

        $response =
            (new PressesStart(
                (new StartMessage($this->userId(), $this->chatId()))->value(),
                $this->botId()->value(),
                new Indifferent(),
                $connection,
                $logs
            ))
                ->response();


    }

    public function testWhenExistingUserPressesStartThenShowAMessageTellingWhatThisUserCanDo()
    {

    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();;
    }

    private function userId(): UserId
    {
        return new FromInteger(654987);
    }

    private function chatId(): ChatId
    {
        return new ChatIdFromInteger(123321);
    }

    private function botId(): BotId
    {
        return new FromString(new Fixed());
    }
}