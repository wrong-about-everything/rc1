<?php

declare(strict_types=1);

namespace RC\Domain\BotUser\WriteModel;

use Ramsey\Uuid\Uuid;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\BotUser\Id\Impure\FromReadModelBotUser;
use RC\Domain\BotUser\ReadModel\ByInternalTelegramUserIdAndBotId;
use RC\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\TransactionalQueryFromMultipleQueries;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId as PureTelegramUserId;

class AddedIfNotYet implements BotUser
{
    private $telegramUserId;
    private $botId;
    private $firstName;
    private $lastName;
    private $telegramHandle;
    private $connection;

    private $cached;

    public function __construct(PureTelegramUserId $telegramUserId, BotId $botId, string $firstName, string $lastName, string $telegramHandle, OpenConnection $connection)
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

    private function doValue(): ImpureValue
    {
        $botUserFromDb = new ByInternalTelegramUserIdAndBotId($this->telegramUserId, $this->botId, $this->connection);
        if (!$botUserFromDb->value()->isSuccessful()) {
            return $botUserFromDb->value();
        }
        if ($botUserFromDb->value()->pure()->isPresent()) {
            return (new FromReadModelBotUser($botUserFromDb))->value();
        }

        $generatedTelegramUserId = Uuid::uuid4()->toString();
        $generatedBotUserId = Uuid::uuid4()->toString();

        $registerUserResponse =
            (new TransactionalQueryFromMultipleQueries(
                [
                    new SingleMutating(
                        <<<q
insert into "telegram_user" (id, first_name, last_name, telegram_id, telegram_handle)
values (?, ?, ?, ?, ?)
-- user might already exist, but bot user does not
on conflict(telegram_id) do nothing
q
                        ,
                        [$generatedTelegramUserId, $this->firstName, $this->lastName, $this->telegramUserId->value(), $this->telegramHandle],
                        $this->connection
                    ),
                    new SingleMutating(
                        <<<q
insert into bot_user (id, user_id, bot_id, status)
values (?, ?, ?, ?)
q
                        ,
                        [$generatedBotUserId, $generatedTelegramUserId, $this->botId->value(), (new RegistrationIsInProgress())->value()],
                        $this->connection
                    )
                ],
                $this->connection
            ))
                ->response();
        if (!$registerUserResponse->isSuccessful()) {
            return $registerUserResponse;
        }

        return new Successful(new Present($generatedBotUserId));
    }
}