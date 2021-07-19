<?php

declare(strict_types=1);

namespace RC\Domain\BotUser;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Domain\BotId\BotId;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;

class ByTelegramUserId implements BotUser
{
    private $telegramUserId;
    private $botId;
    private $connection;
    private $cached;

    public function __construct(TelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
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

    private function doValue()
    {
        $response =
            (new Selecting(
                <<<q
select bu.*
from
    bot_user bu join "user" u on u.id = bu.user_id
where u.telegram_id = ? and bu.bot_id = ?
q
                ,
                [$this->telegramUserId->value(), $this->botId->value()],
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            return $response;
        }
        if (!isset($response->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($response->pure()->raw()[0]));
    }
}