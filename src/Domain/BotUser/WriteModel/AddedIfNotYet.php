<?php

declare(strict_types=1);

namespace RC\Domain\BotUser\WriteModel;

use Ramsey\Uuid\Uuid;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\BotUser\Id\Impure\BotUserId;
use RC\Domain\BotUser\Id\Impure\FromReadModelBotUser;
use RC\Domain\BotUser\Id\Pure\BotUserId as PureBotUserId;
use RC\Domain\BotUser\Id\Pure\Random;
use RC\Domain\BotUser\ReadModel\ByInternalTelegramUserIdAndBotId;
use RC\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use RC\Domain\TelegramUser\ByTelegramId;
use RC\Domain\TelegramUser\UserId\Impure\FromTelegramUser;
use RC\Domain\TelegramUser\UserId\Pure\FromImpure;
use RC\Domain\TelegramUser\UserId\Pure\Random as RandomTelegramUserId;
use RC\Domain\TelegramUser\UserId\Pure\TelegramUserId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\TransactionalQueryFromMultipleQueries;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId as PureInternalTelegramUserId;

class AddedIfNotYet implements BotUser
{
    private $telegramUserId;
    private $botId;
    private $firstName;
    private $lastName;
    private $telegramHandle;
    private $connection;

    private $cached;

    public function __construct(PureInternalTelegramUserId $telegramUserId, BotId $botId, string $firstName, string $lastName, string $telegramHandle, OpenConnection $connection)
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
        $botUser = new ByInternalTelegramUserIdAndBotId($this->telegramUserId, $this->botId, $this->connection);
        if (!$botUser->value()->isSuccessful()) {
            return $botUser->value();
        }
        if ($botUser->value()->pure()->isPresent()) {
            return (new FromReadModelBotUser($botUser))->value();
        }

        $telegramUser = new ByTelegramId($this->telegramUserId, $this->connection);
        if (!$telegramUser->value()->isSuccessful()) {
            return $telegramUser->value();
        }
        if ($telegramUser->value()->pure()->isPresent()) {
            // insert user in a bot with his existing telegram user id, not randomly generated one
            // Clear prod database from orphaned bot_users (the ones with non-existing telegram_user_id)
            $telegramUserId = new FromImpure(new FromTelegramUser($telegramUser));
            $generatedBotUserId = new Random();
            $insertBotUserResponse = $this->insertBotUser($telegramUserId, $generatedBotUserId);
            if (!$insertBotUserResponse->isSuccessful()) {
                return $insertBotUserResponse;
            }
            return new Successful(new Present($generatedBotUserId->value()));
        }

        $generatedTelegramUserId = new RandomTelegramUserId();
        $generatedBotUserId = new Random();

        $registerUserResponse =
            (new TransactionalQueryFromMultipleQueries(
                [
                    new SingleMutating(
                        <<<q
insert into "telegram_user" (id, first_name, last_name, telegram_id, telegram_handle)
values (?, ?, ?, ?, ?)
-- user might already exist, but bot user might not
on conflict(telegram_id) do nothing
q
                        ,
                        [$generatedTelegramUserId->value(), $this->firstName, $this->lastName, $this->telegramUserId->value(), $this->telegramHandle],
                        $this->connection
                    ),
                    $this->insertBotUserQuery($generatedTelegramUserId, $generatedBotUserId)
                ],
                $this->connection
            ))
                ->response();
        if (!$registerUserResponse->isSuccessful()) {
            return $registerUserResponse;
        }

        return new Successful(new Present($generatedBotUserId->value()));
    }

    private function insertBotUser(TelegramUserId $telegramUserId, PureBotUserId $pureBotUserId): ImpureValue
    {
        return $this->insertBotUserQuery($telegramUserId, $pureBotUserId)->response();
    }

    private function insertBotUserQuery(TelegramUserId $telegramUserId, PureBotUserId $botUserId): Query
    {
        return
            new SingleMutating(
                <<<q
insert into bot_user (id, user_id, bot_id, status)
values (?, ?, ?, ?)
q
                ,
                [$botUserId->value(), $telegramUserId->value(), $this->botId->value(), (new RegistrationIsInProgress())->value()],
                $this->connection
            );
    }
}