<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationQuestion;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\TelegramBot\BotId\BotId;
use RC\Infrastructure\TelegramBot\UserId\TelegramUserId;

class CurrentRegistrationQuestion implements RegistrationQuestion
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

    private function doValue(): ImpureValue
    {
        if (!$this->telegramUserId->value()->isSuccessful()) {
            return $this->telegramUserId->value();
        }

        $registrationQuestion =
            (new Selecting(
                <<<q
        select *
        from registration_question rq
            left join user_registration_progress urp on rq.id = urp.registration_question_id
            left join "user" u on urp.user_id = u.id and u.telegram_id = ?
        where rq.bot_id = ? and urp.registration_question_id is null
        order by rq.ordinal_number asc
        limit 1
        q
                ,
                [$this->telegramUserId->value()->pure()->raw(), $this->botId->value()],
                $this->connection
            ))
                ->response();
        if (!$registrationQuestion->isSuccessful()) {
            return $registrationQuestion;
        }
        if (!isset($registrationQuestion->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($registrationQuestion->pure()->raw()[0]));
    }
}