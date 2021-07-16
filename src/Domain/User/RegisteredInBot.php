<?php

declare(strict_types=1);

namespace RC\Domain\User;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\TelegramBot\BotId\BotId;
use RC\Infrastructure\TelegramBot\UserId\TelegramUserId;

class RegisteredInBot implements User
{
    private $userId;
    private $botId;
    private $connection;

    public function __construct(TelegramUserId $userId, BotId $botId, OpenConnection $connection)
    {
        $this->userId = $userId;
        $this->botId = $botId;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        return
            (new Selecting(
                <<<q
select *
from
    user u join user_bot ub on u.id = ub.user_id
where u.id = ? and ub.bot_id = ?
q
                ,
                [$this->userId->value(), $this->botId->value()],
                $this->connection
            ))
                ->response();
    }
}