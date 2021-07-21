<?php

declare(strict_types=1);

namespace RC\UserStories\User\SendsArbitraryMessage;

use RC\Domain\BotId\BotId;
use RC\Domain\BotUser\BotUser;
use RC\Domain\BotUser\ByTelegramUserId;
use RC\Domain\RegistrationProcess\RegistrationQuestion\NextRegistrationQuestion;
use RC\Domain\UserStatus\Pure\Registered;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId as PureTelegramUserId;

class RegisteredIfNoMoreQuestionsLeft implements BotUser
{
    private $telegramUserId;
    private $botId;
    private $connection;

    private $cached;

    public function __construct(PureTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
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
        if (!(new NextRegistrationQuestion($this->telegramUserId, $this->botId, $this->connection))->value()->pure()->isPresent()) {
            $updatedUserResponse =
                (new SingleMutating(
                    <<<q
    update bot_user
    set status = ?
    from "user"
    where "user".id = bot_user.user_id and "user".telegram_id = ? and bot_user.bot_id = ?
    q

                    ,
                    [(new Registered())->value(), $this->telegramUserId->value(), $this->botId->value()],
                    $this->connection
                ))
                    ->response();
            if (!$updatedUserResponse->isSuccessful()) {
                return $updatedUserResponse;
            }
        }

        return (new ByTelegramUserId($this->telegramUserId, $this->botId, $this->connection))->value();
    }
}