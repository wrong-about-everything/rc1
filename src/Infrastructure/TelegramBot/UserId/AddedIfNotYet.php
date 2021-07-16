<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserId;

use Exception;
use Ramsey\Uuid\Uuid;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\TransactionalQueryFromMultipleQueries;
use RC\Infrastructure\TelegramBot\ChatId\ChatId;

class AddedIfNotYet extends TelegramUserId
{
    private $telegramUserId;
    private $name;
    private $chatId;
    private $telegramHandle;
    private $connection;

    public function __construct(TelegramUserId $telegramUserId, string $name, ChatId $chatId, string $telegramHandle, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->name = $name;
        $this->chatId = $chatId;
        $this->telegramHandle = $telegramHandle;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $generatedId = Uuid::uuid4()->toString();
        new TransactionalQueryFromMultipleQueries(
            [
                new SingleMutating(
                    <<<q
insert into user (id, name, telegram_id, telegram_handle)
values (?, ?, ?, ?, ?)
q
                    ,
                    [$generatedId, $this->name, $this->telegramUserId->value(), $this->telegramHandle],
                    $this->connection
                ),
                new SingleMutating(
                    <<<q
insert into user_bot (user_id, bot_id, telegram_chat_id)
values (?, ?, ?)
q
                    ,
                    [$generatedId, $this->name, $this->telegramUserId->value(), $this->chatId->value(), $this->telegramHandle],
                    $this->connection
                ),
            ],
            $this->connection
        );

    }

    public function exists(): bool
    {
        return $this->telegramUserId->exists();
    }
}