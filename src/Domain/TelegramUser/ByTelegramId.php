<?php

declare(strict_types=1);

namespace RC\Domain\TelegramUser;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class ByTelegramId implements TelegramUser
{
    private $telegramUserId;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
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
        $response =
            (new Selecting(
                <<<q
select u.*
from telegram_user u
where u.telegram_id = ?
q
                ,
                [$this->telegramUserId->value()],
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