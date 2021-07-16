<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Stub;

use Exception;
use RC\Domain\BotId\BotId;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;

class BotUser
{
    private $botId;
    private $connection;

    public function __construct(BotId $botId, OpenConnection $connection)
    {
        $this->botId = $botId;
        $this->connection = $connection;
    }

    public function insert(array $record)
    {
        $values = array_merge($this->defaultValues(), $record);
        $userId = $values['id'];
        $userInsertResponse =
            (new SingleMutating(
                'insert into "user" (id, first_name, last_name, telegram_id, telegram_handle) values (?, ?, ?, ?, ?)',
                [$userId, $values['first_name'], $values['last_name'], $values['telegram_id'], $values['telegram_handle']],
                $this->connection
            ))
                ->response();
        if (!$userInsertResponse->isSuccessful()) {
            throw new Exception(sprintf('Error while inserting user record: %s', $userInsertResponse->error()->logMessage()));
        }
        $userBotInsertResponse =
            (new SingleMutating(
                'insert into "user_bot" (user_id, bot_id) values (?, ?)',
                [$userId, $this->botId->value()],
                $this->connection
            ))
                ->response();
        if (!$userBotInsertResponse->isSuccessful()) {
            throw new Exception(sprintf('Error while inserting user_bot record: %s', $userInsertResponse->error()->logMessage()));
        }
    }

    private function defaultValues()
    {
        return [
            'name' => 'Vasily III the Greatest'
        ];
    }
}