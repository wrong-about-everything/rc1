<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserId;

use Ramsey\Uuid\Uuid;
use RC\Domain\BotId\BotId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;

class AddedIfNotYet extends TelegramUserId
{
    private $telegramUserId;
    private $botId;
    private $firstName;
    private $lastName;
    private $telegramHandle;
    private $connection;

    private $cached;

    public function __construct(TelegramUserId $telegramUserId, BotId $botId, string $firstName, string $lastName, string $telegramHandle, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->telegramHandle = $telegramHandle;
        $this->connection = $connection;

        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    public function exists(): bool
    {
        return $this->telegramUserId->exists();
    }

    private function doValue()
    {
        if (!$this->telegramUserId->value()->isSuccessful()) {
            return $this->telegramUserId->value();
        }

        $generatedId = Uuid::uuid4()->toString();
        $insertUserResponse =
            (new SingleMutating(
                <<<q
insert into "user" (id, first_name, last_name, telegram_id, telegram_handle)
values (?, ?, ?, ?, ?)
on conflict(telegram_id) do nothing
q
                ,
                [$generatedId, $this->firstName, $this->lastName, $this->telegramUserId->value()->pure()->raw(), $this->telegramHandle],
                $this->connection
            ))
                ->response();
        if (!$insertUserResponse->isSuccessful()) {
            return $insertUserResponse;
        }

        $insertUserBotResponse =
            (new SingleMutating(
                <<<q
insert into user_bot (user_id, bot_id)
values (?, ?)
on conflict(user_id, bot_id) do nothing
q
                ,
                [$generatedId, $this->botId->value()],
                $this->connection
            ))
                ->response();
        if (!$insertUserBotResponse->isSuccessful()) {
            return $insertUserBotResponse;
        }

        return $this->telegramUserId->value();
    }
}