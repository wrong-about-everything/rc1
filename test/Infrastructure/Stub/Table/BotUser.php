<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Stub\Table;

use Exception;
use Ramsey\Uuid\Uuid;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\User\UserStatus\Pure\RegistrationIsInProgress;
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

    public function insert(array $user, array $botUser)
    {
        $values = array_merge($this->defaultValues(), $user);
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
                'insert into "bot_user" (id, user_id, bot_id, position, experience, about, status) values (?, ?, ?, ?, ?, ?, ?)',
                [Uuid::uuid4()->toString(), $userId, $this->botId->value(), $botUser['position'] ?? null, $botUser['experience'] ?? null, $botUser['about'] ?? null, $botUser['status'] ?? (new RegistrationIsInProgress())->value()],
                $this->connection
            ))
                ->response();
        if (!$userBotInsertResponse->isSuccessful()) {
            throw new Exception(sprintf('Error while inserting bot_user record: %s', $userBotInsertResponse->error()->logMessage()));
        }
    }

    private function defaultValues()
    {
        return [
            'name' => 'Vasily III the Greatest'
        ];
    }
}